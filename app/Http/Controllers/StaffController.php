<?php
namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Appointment;

class StaffController extends Controller
{
    public function index() {
        $myJobs = JobCard::with(['vehicle','appointment.user','jobType'])
            ->where('staff_id', auth()->id())
            ->where('current_stage', '!=', 'completed')
            ->latest()
            ->get();

        $todayAppointments = Appointment::with(['vehicle','user'])
            ->whereDate('date', today())
            ->where('status', 'confirmed')
            ->orderBy('time')
            ->get();

        return view('staff.dashboard', compact('myJobs','todayAppointments'));
    }
}