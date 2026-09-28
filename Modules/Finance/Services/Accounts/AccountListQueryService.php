<?php

namespace Modules\Finance\Services\Accounts;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Builds the Finance List Accounts query without joining every ledger row.
 *
 * The old page joined account_transactions directly and then grouped the full
 * ledger for every request. This query lets DataTables count/filter accounts
 * first and calculates the balance only for the rows returned on the page.
 */
class AccountListQueryService
{
    /**
     * @param  array<int, int>|string  $permittedLocations
     */
    public function build(
        int $businessId,
        Request $request,
        $permittedLocations,
        int $accountAccess
    ): Builder {
        $query = DB::table('accounts')
            ->leftJoin('account_types as ats', 'accounts.account_type_id', '=', 'ats.id')
            ->leftJoin('account_types as pat', 'ats.parent_account_type_id', '=', 'pat.id')
            ->leftJoin('account_groups', 'accounts.asset_type', '=', 'account_groups.id')
            ->leftJoin('accounts as parent_account', 'accounts.parent_account_id', '=', 'parent_account.id')
            ->leftJoin('business_locations as account_location', function ($join): void {
                $join->on('account_location.id', '=', 'accounts.location_id');
            })
            ->leftJoin('users as u', 'accounts.created_by', '=', 'u.id')
            ->where('accounts.business_id', $businessId)
            ->whereNull('accounts.deleted_at')
            ->where('accounts.disabled', 0)
            ->select([
                'accounts.location_id',
                'accounts.name',
                'accounts.parent_account_id',
                'accounts.account_number',
                'accounts.visible',
                'accounts.is_main_account',
                'accounts.note',
                'accounts.id',
                'accounts.account_type_id',
                'accounts.created_by',
                'accounts.disabled',
                'accounts.asset_type',
                'accounts.is_closed',
                'ats.name as account_type_name',
                'pat.name as parent_account_type_name',
                'account_groups.name as group_name',
                'parent_account.name as parent_account_name',
                'account_location.name as account_location_name',
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
            ]);

        /*
         * Calculate one debit-normal balance per returned account. Main accounts
         * include their direct child accounts. Because List Accounts now uses
         * server-side pagination, this indexed subquery runs only for the small
         * page of accounts being displayed instead of scanning the entire ledger
         * before the page can render.
         */
        $balanceSql = <<<'SQL'
(
    SELECT COALESCE(SUM(
        CASE
            WHEN atb.transaction_payment_id IS NULL OR tpb.deleted_at IS NULL THEN
                CASE WHEN atb.type = 'credit' THEN -1 * atb.amount ELSE atb.amount END
            ELSE 0
        END
    ), 0)
    FROM account_transactions AS atb
    INNER JOIN accounts AS tx_account ON tx_account.id = atb.account_id
    LEFT JOIN transaction_payments AS tpb ON tpb.id = atb.transaction_payment_id
    WHERE tx_account.business_id = ?
      AND atb.deleted_at IS NULL
      AND (tx_account.id = accounts.id OR tx_account.parent_account_id = accounts.id)
)
SQL;
        $query->selectRaw($balanceSql . ' AS debit_normal_balance', [$businessId]);

        $accountType = $request->input('account_type_s');
        $accountSubType = $request->input('account_sub_type');

        if (! empty($accountType) && $accountType !== 'All') {
            if (! empty($accountSubType) && $accountSubType !== 'All') {
                $query->where('accounts.account_type_id', $accountSubType);
            } else {
                $subTypeIds = DB::table('account_types')
                    ->where('business_id', $businessId)
                    ->where('parent_account_type_id', $accountType)
                    ->pluck('id');

                if ($subTypeIds->isNotEmpty()) {
                    $query->whereIn('accounts.account_type_id', $subTypeIds);
                } else {
                    $query->where('accounts.account_type_id', $accountType);
                }
            }
        } elseif (! empty($accountSubType) && $accountSubType !== 'All') {
            $query->where('accounts.account_type_id', $accountSubType);
        }

        $accountGroup = $request->input('account_group');
        if (! empty($accountGroup) && $accountGroup !== 'All') {
            $query->where('accounts.asset_type', $accountGroup);
        }

        if ($permittedLocations !== 'all') {
            $query->where(function (Builder $locationQuery) use ($permittedLocations): void {
                $locationQuery->whereIn('accounts.location_id', (array) $permittedLocations)
                    ->orWhereNull('accounts.location_id')
                    ->orWhere('accounts.location_id', '')
                    ->orWhere('accounts.location_id', 'all');
            });
        }

        $locationId = $request->input('location_id');
        if (! empty($locationId) && $locationId !== 'all') {
            /*
             * MA-002 (Issue 2): "List Accounts does not show all the Accounts".
             *
             * accounts.location_id is varchar(20) NOT NULL DEFAULT 'all'.
             * In the live tenant database 682 of 687 accounts carry the
             * literal value 'all', meaning "available at every location".
             *
             * The previous filter was a plain equality test, so choosing a
             * specific business location matched ONLY the handful of accounts
             * pinned to that location and silently hid every 'all' account -
             * 51 accounts became 3 for business 2.
             *
             * An account marked 'all' belongs to the selected location too,
             * so it must remain visible alongside the location-specific ones.
             */
            $query->where(function (Builder $selectedLocationQuery) use ($locationId): void {
                $selectedLocationQuery->where('accounts.location_id', $locationId)
                    ->orWhere('accounts.location_id', 'all')
                    ->orWhere('accounts.location_id', '')
                    ->orWhereNull('accounts.location_id');
            });
        }

        $parentAccountId = $request->input('parent_account_id');
        if (! empty($parentAccountId) && $parentAccountId !== 'All') {
            $query->where('accounts.parent_account_id', $parentAccountId);
        }

        $accountId = $request->input('account_name');
        if (! empty($accountId) && $accountId !== 'All') {
            $query->where('accounts.id', $accountId);
        }

        if ($accountAccess === 0) {
            $query->where(function (Builder $accessQuery): void {
                $accessQuery->whereIn('accounts.name', [
                    'Accounts Receivable',
                    'Accounts Payable',
                    'Cards (Credit Debit) Account',
                    'Cash',
                    'Cheques in Hand',
                    'Customer Deposits',
                    'Petty Cash',
                ])->orWhere('accounts.visible', 1);
            });
        }

        return $query;
    }
}
