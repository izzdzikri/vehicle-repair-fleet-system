<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Determine whether the user can view any vehicles.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the vehicle.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        if (in_array($user->role, ['admin', 'staff', 'coordinator'], true)) {
            return true;
        }

        if ($user->role === 'corporate') {
            if (! $user->company_id) {
                return false;
            }
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');

            return $companyUserIds->contains($vehicle->user_id);
        }

        return $vehicle->user_id === $user->id;
    }

    /**
     * Determine whether the user can create vehicles.
     */
    public function create(User $user): bool
    {
        if ($user->role === 'corporate' && $user->isSecondaryPic()) {
            return false;
        }

        return in_array($user->role, ['admin', 'corporate', 'individual'], true);
    }

    /**
     * Determine whether the user can update the vehicle.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'corporate') {
            if ($user->isSecondaryPic() || ! $user->company_id) {
                return false;
            }
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');

            return $companyUserIds->contains($vehicle->user_id);
        }

        return $vehicle->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the vehicle.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'corporate') {
            if ($user->isSecondaryPic() || ! $user->company_id) {
                return false;
            }
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');

            return $companyUserIds->contains($vehicle->user_id);
        }

        return $vehicle->user_id === $user->id;
    }
}
