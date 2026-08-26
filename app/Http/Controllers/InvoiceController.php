<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\JobCard;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index() {
        $user = auth()->user();

        if (in_array($user->role, ['admin', 'staff'])) {
            $invoices = Invoice::with(['jobCard.vehicle', 'jobCard.appointment.user'])
                ->latest()->get();

            $pendingJobCards = JobCard::with(['vehicle', 'appointment.user'])
                ->where('current_stage', 'completed')
                ->whereDoesntHave('invoice')
                ->latest()
                ->get();

        } elseif ($user->role === 'corporate') {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');
            $invoices = Invoice::with(['jobCard.vehicle', 'jobCard.appointment.user'])
                ->whereHas('jobCard.appointment', fn($q) => $q->whereIn('user_id', $companyUserIds))
                ->latest()->get();
            $pendingJobCards = collect();

        } else {
            $invoices = Invoice::with(['jobCard.vehicle', 'jobCard.appointment.user'])
                ->whereHas('jobCard.appointment', fn($q) => $q->where('user_id', $user->id))
                ->latest()->get();
            $pendingJobCards = collect();
        }

        return view('invoices.index', compact('invoices', 'pendingJobCards'));
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

    public function generate(Request $request) {
        $request->validate([
            'job_card_id' => 'required|exists:job_cards,id',
            'tax_rate'    => 'nullable|numeric|min:0|max:100',
            'due_date'    => 'nullable|date',
        ]);

        $jobCard = JobCard::findOrFail($request->job_card_id);
        abort_unless($jobCard->current_stage === 'completed', 400, 'Job must be completed before invoicing.');

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

        $prefix = auth()->user()->role === 'admin' ? 'admin' : 'staff';
        return redirect("/{$prefix}/invoices/{$invoice->id}")->with('success', 'Invoice generated.');
    }

    public function addPayment(Request $request, Invoice $invoice) {
        $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'method'       => 'required|in:cash,bank_transfer,card,online',
            'reference_no' => 'nullable|string|max:100',
            'paid_at'      => 'nullable|date',
        ]);

        Payment::create([
            'invoice_id'   => $invoice->id,
            'amount'       => $request->amount,
            'method'       => $request->method,
            'reference_no' => $request->reference_no,
            'paid_at'      => $request->paid_at ?? now(),
            'recorded_by'  => auth()->id(),
        ]);

        $invoice->recalculate();

        return back()->with('success', 'Payment recorded.');
    }
}