<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any appointments.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        if (in_array($user->role, ['admin', 'staff', 'coordinator'], true)) {
            return true;
        }

        if ($user->role === 'corporate' && $user->company_id && $appointment->user_id) {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');

            return $companyUserIds->contains($appointment->user_id);
        }

        return $appointment->user_id === $user->id;
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        if ($user->status === 'inactive') {
            return false;
        }

        if ($user->role === 'corporate' && $user->isSecondaryPic()) {
            return false;
        }

        return in_array($user->role, ['admin', 'staff', 'coordinator', 'corporate', 'individual'], true);
    }

    /**
     * Determine whether the user can cancel the appointment.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        if (in_array($user->role, ['admin', 'staff', 'coordinator'], true)) {
            return true;
        }

        return $appointment->user_id === $user->id && $appointment->status === 'pending';
    }
}
