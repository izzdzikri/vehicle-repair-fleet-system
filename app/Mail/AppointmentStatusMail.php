<?php
namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment, public string $statusLabel)
    {
    }

    public function build()
    {
        return $this->subject('Appointment ' . $this->statusLabel . ' — Teraju Setia Enterprise')
            ->view('emails.appointment-status');
    }
}