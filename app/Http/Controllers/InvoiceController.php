<?php
namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Payment;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function index(Request $request) {
        $user    = auth()->user();
        $search  = trim((string) $request->input('search'));
        $perPage = 15;

        if (in_array($user->role, ['admin', 'staff'])) {
            $base = Invoice::query();
        } elseif ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $base = Invoice::whereHas('jobCard.appointment', fn($q) => $q->whereIn('user_id', $companyUserIds));
        } else {
            $base = Invoice::whereHas('jobCard.appointment', fn($q) => $q->where('user_id', $user->id));
        }

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $invoices = $base->with(['jobCard.vehicle', 'jobCard.appointment.user'])
            ->latest()->paginate($perPage)->withQueryString();

        $pendingJobCards = collect();
        if (in_array($user->role, ['admin', 'staff'])) {
            $pendingJobCards = JobCard::with(['vehicle', 'appointment.user'])
                ->where('current_stage', 'completed')
                ->whereDoesntHave('invoice')
                ->latest()
                ->get();
        }

        return view('invoices.index', compact('invoices', 'pendingJobCards', 'search'));
    }

    public function show(Invoice $invoice) {
        $user = auth()->user();
        $invoice->load([
            'jobCard.vehicle', 'jobCard.appointment.user', 'jobCard.staff',
            'jobCard.parts.sparePart', 'jobCard.labourCharges', 'payments.recorder',
        ]);

        if (!in_array($user->role, ['admin', 'staff'])) {
            $ownerId = $invoice->jobCard->appointment->user_id ?? null;
            $allowed = $ownerId === $user->id;

            if ($user->role === 'corporate' && $ownerId) {
                $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
                $allowed = $companyUserIds->contains($ownerId);
            }

            abort_unless($allowed, 403);
        }

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Download invoice as a PDF using dompdf.
     * Applies the same scope/ownership guard as show().
     */
    public function downloadPdf(Invoice $invoice) {
        $user = auth()->user();
        $invoice->load([
            'jobCard.vehicle', 'jobCard.appointment.user', 'jobCard.staff',
            'jobCard.parts.sparePart', 'jobCard.labourCharges', 'payments.recorder',
            'jobCard.jobType',
        ]);

        if (!in_array($user->role, ['admin', 'staff'])) {
            $ownerId = $invoice->jobCard->appointment->user_id ?? null;
            $allowed = $ownerId === $user->id;
            if ($user->role === 'corporate' && $ownerId) {
                $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
                $allowed = $companyUserIds->contains($ownerId);
            }
            abort_unless($allowed, 403);
        }

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');

        $filename = 'Invoice-' . $invoice->invoice_number . '.pdf';
        return $pdf->download($filename);
    }

    public function generate(Request $request) {
        if (!auth()->user()->hasPermission('invoice.manage')) {
            return back()->with('error', 'You do not have permission to generate invoices. Contact an admin or the assigned accountant.');
        }

        $request->validate([
            'job_card_id' => 'required|exists:job_cards,id',
            'tax_rate'    => 'nullable|numeric|min:0|max:100',
            'due_date'    => 'nullable|date',
        ]);

        $jobCard = JobCard::findOrFail($request->job_card_id);

        if ($jobCard->current_stage !== 'completed') {
            return back()->with('error', 'Job must be completed before it can be invoiced.');
        }

        if ($jobCard->invoice) {
            return back()->with('error', 'This job card already has an invoice.');
        }

        $partsCost  = $jobCard->parts()->sum(DB::raw('quantity * unit_price'));
        $labourCost = $jobCard->labourCharges()->sum('charge');
        $subtotal   = $partsCost + $labourCost;

        $taxRate   = (float) $request->input('tax_rate', 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total     = $subtotal + $taxAmount;

        $customerName = $jobCard->appointment && $jobCard->appointment->is_walkin
            ? ($jobCard->appointment->walkin_name ?? 'Walk-in Customer')
            : ($jobCard->appointment->user->name ?? '—');

        $invoice = Invoice::create([
            'job_card_id'    => $jobCard->id,
            'invoice_number' => 'PENDING',
            'customer_name'  => $customerName,
            'subtotal'       => $subtotal,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'total'          => $total,
            'due_date'       => $request->input('due_date'),
            'generated_by'   => auth()->id(),
        ]);

        $invoice->update([
            'invoice_number' => 'INV-' . now()->format('Y') . '-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT),
        ]);

        $invoice->recalculate();
        $this->sendInvoiceEmail($invoice);

        $ownerId = $jobCard->appointment->user_id ?? null;
        if ($ownerId) {
            $owner  = User::find($ownerId);
            $prefix = ($owner && $owner->role === 'corporate') ? '/client' : '/customer';
            Notification::send(
                $ownerId,
                'New Invoice',
                "Invoice {$invoice->invoice_number} for RM".number_format($invoice->total,2)." is ready.",
                "{$prefix}/invoices/{$invoice->id}"
            );
        }

        ActivityLog::record(
            'invoice.generated',
            "Generated invoice {$invoice->invoice_number} for {$invoice->customer_name} (RM".number_format($invoice->total,2).")",
            $invoice
        );

        $prefix = auth()->user()->role === 'admin' ? 'admin' : 'staff';
        return redirect("/{$prefix}/invoices/{$invoice->id}")->with('success', 'Invoice generated and emailed to the customer.');
    }

    public function addPayment(Request $request, Invoice $invoice) {
        if (!auth()->user()->hasPermission('invoice.manage')) {
            return back()->with('error', 'You do not have permission to record payments. Contact an admin or the assigned accountant.');
        }

        $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'method'       => 'required|in:cash,bank_transfer,card,online',
            'reference_no' => 'nullable|string|max:100',
            'paid_at'      => 'nullable|date',
        ]);

        if ($request->amount > $invoice->balance) {
            return back()->with('error', 'Payment exceeds the outstanding balance.');
        }

        Payment::create([
            'invoice_id'   => $invoice->id,
            'amount'       => $request->amount,
            'method'       => $request->method,
            'reference_no' => $request->reference_no,
            'paid_at'      => $request->paid_at ?? now(),
            'recorded_by'  => auth()->id(),
        ]);

        $invoice->recalculate();
        $this->sendInvoiceEmail($invoice);

        ActivityLog::record(
            'invoice.payment',
            "Recorded RM".number_format($request->amount,2)." payment for invoice {$invoice->invoice_number}",
            $invoice
        );

        return back()->with('success', 'Payment recorded.');
    }

    /**
     * Manually re-send an invoice email (e.g. customer says they never got it).
     */
    public function resend(Invoice $invoice) {
        $invoice->load('jobCard.appointment.user');
        $email = $invoice->jobCard->appointment->user->email ?? null;

        if (!$email) {
            return back()->with('error', 'This customer has no email on file (walk-in customer or missing email address).');
        }

        try {
            Mail::to($email)->send(new InvoiceMail($invoice));
            return back()->with('success', "Invoice emailed to {$email}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    private function sendInvoiceEmail(Invoice $invoice): void {
        $invoice->load('jobCard.appointment.user');
        $email = $invoice->jobCard->appointment->user->email ?? null;
        if (!$email) return;

        try {
            Mail::to($email)->send(new InvoiceMail($invoice));
        } catch (\Throwable $e) {
            Log::warning('Invoice email failed: ' . $e->getMessage());
        }
    }
}