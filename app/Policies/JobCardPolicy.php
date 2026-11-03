<?php

namespace App\Policies;

use App\Models\JobCard;
use App\Models\User;

class JobCardPolicy
{
    /**
     * Determine whether the user can view any job cards.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff', 'coordinator'], true);
    }

    /**
     * Determine whether the user can view the job card.
     */
    public function view(User $user, JobCard $jobCard): bool
    {
        if (in_array($user->role, ['admin', 'staff', 'coordinator'], true)) {
            return true;
        }

        if ($user->role === 'corporate' && $user->company_id) {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');

            return $companyUserIds->contains($jobCard->vehicle?->user_id);
        }

        return $jobCard->vehicle?->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the job card.
     */
    public function update(User $user, JobCard $jobCard): bool
    {
        if (in_array($user->role, ['admin', 'coordinator'], true)) {
            return true;
        }

        if ($user->hasPermission('job_cards.manage_all')) {
            return true;
        }

        return $jobCard->staff_id === $user->id;
    }

    /**
     * Determine whether the user can delete the job card.
     */
    public function delete(User $user, JobCard $jobCard): bool
    {
        return $user->role === 'admin';
    }
}
