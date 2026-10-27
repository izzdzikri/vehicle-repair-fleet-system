<?php
namespace App\Mail;

use App\Models\Vehicle;
use App\Models\JobType;
use App\Models\MaintenanceAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MaintenanceAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Vehicle $vehicle,
        public JobType $jobType,
        public MaintenanceAlert $alert,
        public bool $isEscalation = false
    ) {}

    public function build()
    {
        $prefix = $this->alert->urgency === 'high' ? '⚠️ URGENT: ' : '🔮 REMINDER: ';
        $subject = $prefix . $this->vehicle->plate_number . ' — ' . $this->jobType->name . ' Due Soon';

        return $this->subject($subject)
            ->view('emails.maintenance-alert');
    }
}
