<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\SW\Services\ShiftTableService;

/**
 * SW Shift Operations - cash status, shift lifecycle, and Collection Summary.
 *
 * Collection Summary also appears on SW Payments. Same partial, so a change
 * there shows in both places.
 */
class DailyShiftsPageController extends Controller
{
    public function __construct(protected ShiftTableService $shiftTable)
    {
    }

    protected function businessId(): int
    {
        // In tenant mode numeric business IDs can repeat between databases and
        // session state can lag behind the authenticated tenant user. Prefer the
        // current user, then fall back to the long-established session keys.
        $userBusinessId = (int) (optional(auth()->user())->business_id ?? 0);

        return $userBusinessId > 0
            ? $userBusinessId
            : (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function index()
    {
        $businessId = $this->businessId();

        abort_unless($this->canSeeAnyTab(), 403);

        return view('sw::daily_shifts.index', $this->sharedData($businessId));
    }

    protected function canSeeAnyTab(): bool
    {
        $user = auth()->user();

        if ($user->can('superadmin')) {
            return true;
        }

        foreach ([
            'sw.daily_cash_status.view',
            'sw.daily_shift.view',
            'sw.collection_summary.view',
        ] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    protected function sharedData(int $businessId): array
    {
        /*
         | Do not use Schema::hasColumn() on this tenant page. tenant.context has
         | already selected the tenant database and the ShiftTableService checks
         | live DATABASE() metadata. Keeping all three tabs on the same rule
         | prevents one stale schema lookup from breaking only one tab/page.
        */
        $locations = collect();
        if ($businessId > 0
            && $this->shiftTable->tableExists('business_locations')
            && $this->shiftTable->hasColumn('business_locations', 'id')
            && $this->shiftTable->hasColumn('business_locations', 'business_id')
            && $this->shiftTable->hasColumn('business_locations', 'name')) {
            try {
                $query = DB::table('business_locations')->where('business_id', $businessId);
                if ($this->shiftTable->hasColumn('business_locations', 'is_active')) {
                    $query->where('is_active', 1);
                }
                $locations = $query->orderBy('name')->pluck('name', 'id');
            } catch (\Throwable $e) {
                $locations = collect();
            }
        }

        $operators = collect();
        if ($businessId > 0
            && $this->shiftTable->tableExists('pump_operators')
            && $this->shiftTable->hasColumn('pump_operators', 'id')
            && $this->shiftTable->hasColumn('pump_operators', 'business_id')
            && $this->shiftTable->hasColumn('pump_operators', 'name')) {
            try {
                $query = DB::table('pump_operators')->where('business_id', $businessId);
                if ($this->shiftTable->hasColumn('pump_operators', 'active')) {
                    $query->where('active', 1);
                }
                $operators = $query->orderBy('name')->pluck('name', 'id');
            } catch (\Throwable $e) {
                $operators = collect();
            }
        }

        return [
            'business_locations' => $locations,
            'default_location' => $locations->keys()->first(),
            'operator_list' => $operators,

            // IS2270 - render the shift rows with the page itself. DataTables is
            // now client-side on this screen, so /sw/shifts and
            // /sw/shift-operations cannot fail with a tn/7 Ajax warning.
            'daily_shift_rows' => $this->shiftTable->rows($businessId),
        ];
    }
}
