<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    private function canManage(User $user): bool
    {
        return $user->hasAnyRole(['Owner', 'Staff']);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManage($user) || $user->hasRole('Client');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->organization_id !== $invoice->organization_id) {
            return false;
        }

        if ($this->canManage($user)) {
            return true;
        }

        if (! $user->hasRole('Client')) {
            return false;
        }

        $clientId = Client::query()
            ->where('user_id', $user->id)
            ->value('id');

        return $clientId !== null && $invoice->client_id === $clientId;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user)
            && $user->organization_id === $invoice->organization_id
            && $invoice->status === 'Draft';
    }

    public function markSent(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user)
            && $user->organization_id === $invoice->organization_id
            && $invoice->status === 'Draft';
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user)
            && $user->organization_id === $invoice->organization_id
            && in_array($invoice->status, ['Sent', 'Overdue'], true);
    }

    public function void(User $user, Invoice $invoice): bool
    {
        return $this->canManage($user)
            && $user->organization_id === $invoice->organization_id
            && $invoice->status !== 'Paid'
            && $invoice->status !== 'Void';
    }
}

