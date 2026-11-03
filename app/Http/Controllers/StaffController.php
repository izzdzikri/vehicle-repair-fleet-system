<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\JobCard;

class StaffController extends Controller
{
    public function index()
    {
        $myJobs = JobCard::with(['vehicle', 'appointment.user', 'jobType'])
            ->where('staff_id', auth()->id())
            ->where('current_stage', '!=', 'completed')
            ->latest()
            ->get();

        $todayAppointments = Appointment::with(['vehicle', 'user'])
            ->where('date', today()->toDateString())
            ->where('status', 'confirmed')
            ->orderBy('time')
            ->get();

        return view('staff.dashboard', compact('myJobs', 'todayAppointments'));
    }
}
