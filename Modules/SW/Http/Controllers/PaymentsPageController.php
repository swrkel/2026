<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\SW\Services\PaymentTabPermissionService;

/**
 * SW Payments - the six recording tabs.
 *
 * The partials do their own loading over AJAX, so this only supplies what they
 * need to render: locations and the operator list for their filters.
 */
class PaymentsPageController extends Controller
{
    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function index()
    {
        $businessId = $this->businessId();

        /*
         | Refused when the user has none of the tabs.
         |
         | The page itself checks each tab, but arriving at a page with nothing
         | on it is worse than being told plainly.
        */
        $businessTabStates = app(PaymentTabPermissionService::class)->states($businessId);

        abort_unless($this->canSeeAnyTab($businessTabStates), 403);

        return view('sw::payments.index', array_merge(
            $this->sharedData($businessId),
            ['swBusinessTabStates' => $businessTabStates]
        ));
    }

    protected function canSeeAnyTab(array $businessTabStates): bool
    {
        $user = auth()->user();
        $isSuperadmin = $user->can('superadmin');

        $paymentTabs = [
            'daily_cash' => 'sw.daily_cash.view',
            'daily_credit_sales' => 'sw.daily_credit_sales.view',
            'daily_cards' => 'sw.daily_cards.view',
            'daily_shortage_excess' => 'sw.daily_shortage_excess.view',
            'daily_cheques' => 'sw.daily_cheques.view',
        ];

        foreach ($paymentTabs as $tab => $permission) {
            if (! empty($businessTabStates[$tab])
                && ($isSuperadmin || $user->can($permission))) {
                return true;
            }
        }

        // Collection Summary is not one of the five business-level SW Payment
        // tabs requested for Manage New, so it keeps its existing role gate.
        return $isSuperadmin || $user->can('sw.collection_summary.view');
    }

    /** What every tab's filters need. */
    protected function sharedData(int $businessId): array
    {
        $locations = DB::table('business_locations')
            ->where('business_id', $businessId)
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('business_locations', 'is_active'),
                fn ($q) => $q->where('is_active', 1)
            )
            ->orderBy('name')
            ->pluck('name', 'id');

        return [
            'business_locations' => $locations,
            'default_location' => $locations->keys()->first(),
            'operator_list' => DB::table('pump_operators')
                ->where('business_id', $businessId)
                ->where('active', 1)
                ->orderBy('name')
                ->pluck('name', 'id'),

            // IS2230: the Daily Cards filter previously received no
            // card_accounts variable at all, so its Card Type dropdown was
            // empty even though the Add form could see the card accounts.
            'card_accounts' => $this->cardAccounts($businessId),
        ];
    }

    /**
     * The same receiving-card catalogue used by DailyCardController.
     *
     * Card Type is a Finance account in the "Card" group. "Own Cards" is not a
     * customer receipt type and is deliberately kept out.
     */
    protected function cardAccounts(int $businessId)
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('accounts')
            || ! \Illuminate\Support\Facades\Schema::hasTable('account_groups')
            || ! \Illuminate\Support\Facades\Schema::hasColumn('accounts', 'asset_type')) {
            return collect();
        }

        return DB::table('accounts')
            ->join('account_groups', 'account_groups.id', '=', 'accounts.asset_type')
            ->where('accounts.business_id', $businessId)
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('account_groups', 'business_id'),
                fn ($q) => $q->where('account_groups.business_id', $businessId)
            )
            ->whereRaw('LOWER(TRIM(account_groups.name)) = ?', ['card'])
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('accounts', 'deleted_at'),
                fn ($q) => $q->whereNull('accounts.deleted_at')
            )
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('accounts', 'is_closed'),
                fn ($q) => $q->where('accounts.is_closed', 0)
            )
            ->orderBy('accounts.name')
            ->pluck('accounts.name', 'accounts.id');
    }
}
