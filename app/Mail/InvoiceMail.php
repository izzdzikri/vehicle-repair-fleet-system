<?php
namespace App\Mail;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function build()
    {
        $this->invoice->loadMissing([
            'jobCard.vehicle', 'jobCard.appointment.user', 'jobCard.staff',
            'jobCard.parts.sparePart', 'jobCard.labourCharges', 'payments.recorder',
            'jobCard.jobType',
        ]);

        $mail = $this->subject('Invoice ' . $this->invoice->invoice_number . ' — Teraju Setia Enterprise')
            ->view('emails.invoice');

        try {
            $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $this->invoice])
                ->setPaper('a4', 'portrait');

            $mail->attachData(
                $pdf->output(),
                'Invoice-' . $this->invoice->invoice_number . '.pdf',
                ['mime' => 'application/pdf']
            );
        } catch (\Throwable $e) {
            Log::warning('Invoice PDF attachment could not be rendered: ' . $e->getMessage());
        }

        return $mail;
    }
}