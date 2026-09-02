<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffPerformanceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param \Illuminate\Support\Collection $report  Rows built by StaffManagementController::buildPerformanceReport()
     */
    public function __construct(public $report, public string $periodLabel)
    {
    }

    public function build()
    {
        return $this->subject('Staff Performance Report — ' . $this->periodLabel)
            ->view('emails.staff-performance');
    }
}