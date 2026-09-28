<?php

namespace Modules\Purchase\Utils;

class PurchaseAccessUtil
{
    public function canView(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.entry.view')
            || $user->can('purchase.view')
            || $user->can('purchase.entry.create')
            || $user->can('purchase.create');
    }

    public function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.entry.create')
            || $user->can('purchase.create');
    }

    public function canEdit(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.entry.edit')
            || $user->can('purchase.update');
    }

    public function canDelete(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.entry.delete')
            || $user->can('purchase.delete');
    }

    public function canPrint(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.entry.print')
            || $this->canView();
    }

    /**
     * Permission used by the per-purchase "Add Payment" action.
     *
     * Keep both the standalone Purchase permissions and the compatible legacy
     * Purchase permissions so existing Business Admin roles do not unexpectedly
     * lose access after this route is restored.
     */
    public function canAddPayments(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.supplier_payment.create')
            || $user->can('purchase.payment.create')
            || $user->can('purchase.entry.edit')
            || $user->can('purchase.update')
            || $user->can('purchase.create');
    }

    public function canViewReturns(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.return.view')
            || $user->can('purchase.view')
            || $user->can('purchase.return.create')
            || $user->can('purchase.update');
    }

    public function canCreateReturns(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.return.create')
            || $user->can('purchase.update');
    }

    public function canEditReturns(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.return.edit')
            || $user->can('purchase.update');
    }

    public function canDeleteReturns(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $this->hasSuperAdminBypass()
            || $user->can('purchase.return.delete')
            || $user->can('purchase.delete');
    }

    public function hasSuperAdminBypass(): bool
    {
        return \App\Services\Authorization\SuperAdminImpersonation::isActive(
            auth()->user(),
            request()
        );
    }
}
