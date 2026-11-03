<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if (in_array($user->role, ['admin', 'staff', 'coordinator'], true)) {
            return true;
        }

        $ownerId = $invoice->jobCard?->appointment?->user_id
            ?? $invoice->jobCard?->vehicle?->user_id;

        if ($user->role === 'corporate' && $user->company_id && $ownerId) {
            $companyUserIds = User::where('company_id', $user->company_id)->pluck('id');

            return $companyUserIds->contains($ownerId);
        }

        return $ownerId === $user->id;
    }

    /**
     * Determine whether the user can generate an invoice.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('invoice.manage');
    }

    /**
     * Determine whether the user can record payment on an invoice.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoice.manage');
    }
}
