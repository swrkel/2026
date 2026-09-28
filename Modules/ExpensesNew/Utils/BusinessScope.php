<?php

namespace Modules\ExpensesNew\Utils;

use Illuminate\Support\Facades\Auth;

class BusinessScope
{
    /**
     * Resolve the business currently selected in the ERP session.
     *
     * A tenant can contain multiple businesses. The selected session business
     * must therefore take precedence over the user's default business_id.
     */
    public static function businessId(): int
    {
        $session = request()->hasSession() ? request()->session() : null;

        $candidates = [
            $session?->get('user.business_id'),
            $session?->get('business.id'),
            $session?->get('business_id'),
            session('user.business_id'),
            session('business.id'),
            session('business_id'),
            Auth::user()?->business_id,
        ];

        foreach ($candidates as $candidate) {
            $businessId = (int) $candidate;
            if ($businessId > 0) {
                return $businessId;
            }
        }

        abort(403, 'Unable to resolve the active business for Expenses-New.');
    }

    public static function locationId(): ?int
    {
        $locationId = request('location_id')
            ?? (request()->hasSession() ? request()->session()->get('user.location_id') : null)
            ?? session('user.location_id');

        return (int) $locationId > 0 ? (int) $locationId : null;
    }
}
