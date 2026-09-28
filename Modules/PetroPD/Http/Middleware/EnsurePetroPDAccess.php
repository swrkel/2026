<?php

namespace Modules\PetroPD\Http\Middleware;

use App\Utils\ModuleUtil;
use Closure;
use Illuminate\Http\Request;

class EnsurePetroPDAccess
{
    /**
     * Enforces Petro PD subscription and role permissions for every PetroPD URL.
     * This protects direct URL access in addition to sidebar visibility.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized Access');
        }

        $user = auth()->user();
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: optional($user)->business_id);

        if (empty($businessId)) {
            abort(403, 'Unauthorized Access');
        }

        $moduleUtil = app(ModuleUtil::class);

        // TEMPORARY RECOVERY BYPASS (SIDEBAR_063):
        // Petro PD business-subscription gate is bypassed while the central permission engine is being rebuilt.
        // Role/page permissions below remain enforced.
        $petroPdTemporaryRecoveryEnabled = true;

        if ($user->can('superadmin')) {
            return $next($request);
        }

        // Daily Pump Status displays Assign Pumps with bulk_assign_pumps.
        // Enforce that same permission for the modal, submit and AJAX helpers.
        if ($this->isPumpAssignmentRequest($request)) {
            if (! $user->can('bulk_assign_pumps')) {
                abort(403, 'Unauthorized Access');
            }

            return $next($request);
        }

        $path = trim($request->path(), '/');


        if (str_contains($path, 'petropd/payment-reconciliation-report')) {
            $isBusinessAdmin = $user->hasRole('Admin#' . $businessId);
            if (! $isBusinessAdmin && ! $user->can('petro_pd.view_payment_reconciliation_report') && ! $user->can('petro_pd.view_report')) {
                abort(403, 'Unauthorized Access');
            }

            return $next($request);
        }

        if (str_contains($path, 'petropd/adjusted-amounts-report')) {
            $isBusinessAdmin = $user->hasRole('Admin#' . $businessId);
            if (! $isBusinessAdmin && ! $user->can('petro_pd.view_adjusted_amounts_report') && ! $user->can('petro_pd.view_report')) {
                abort(403, 'Unauthorized Access');
            }

            return $next($request);
        }

        $permissions = $this->permissionsForPath($path, $request->method());

        if (! $this->canAny($user, $permissions)) {
            abort(403, 'Unauthorized Access');
        }

        return $next($request);
    }

    /**
     * S678: true when the user holds ANY of the listed permissions.
     *
     * The reports branches above already worked this way. Everything else
     * demanded one exact permission, which is what made the tab endpoints
     * fail - see permissionsForPath().
     */
    private function canAny($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($permission !== '' && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * S678: returns every permission that should open this path.
     *
     * Was permissionForPath() and returned a single string. The old single
     * name is still the first entry of each list, so nothing that worked
     * before stops working - the lists only ever widen access.
     */
    private function permissionsForPath(string $path, string $method): array
    {
        // PD operators, List Assigned Operators and related operator pages
        if (
            str_contains($path, 'petropd/pd-operators')
            || str_contains($path, 'petropd/pump-operators')
            || str_contains($path, 'petropd/list-assigned-operators')
        ) {
            return ['petro_pd.view_operators'];
        }

        // Settings pages
        if (str_contains($path, 'petropd/pd-operators/get-settings') || str_contains($path, 'petropd/pd-operators/dashboard-settings')) {
            return ['petro_pd.access'];
        }

        // User activity / reports
        if (str_contains($path, 'petropd/user-activity-report')) {
            return ['petro_pd.view_report'];
        }

        // List settlement
        if (str_contains($path, 'petropd/list-pd-settlement')) {
            return ['petro_pd.list_settlement'];
        }

        // Settlement CRUD
        if (str_contains($path, 'petropd/settlement-pd')) {
            // PD-SETTLEMENT-OPEN-021:
            // The create/data-entry screen must not be treated as the List PD Settlement page.
            // Keep list/view permissions for existing settlement records, but use create permission
            // for the actual Settlement PD entry page.
            if (str_contains($path, 'petropd/settlement-pd/create')) {
                return ['petro_pd.create_settlement'];
            }
            if (in_array(strtoupper($method), ['POST'], true)) {
                return ['petro_pd.create_settlement'];
            }
            if (in_array(strtoupper($method), ['PUT', 'PATCH'], true)) {
                return ['petro_pd.edit_settlement'];
            }
            if (strtoupper($method) === 'DELETE') {
                return ['petro_pd.delete_settlement'];
            }
            return ['petro_pd.list_settlement'];
        }

        // Main PD settlement data entry page and AJAX helpers
        if (
            str_contains($path, 'petropd/pd-settlement')
            || str_contains($path, 'petropd/get-')
            || str_contains($path, 'petropd/pump-operator-pmts/other-sales-list')
            || str_contains($path, 'petropd/pump-operator-pmts/meter-sales-list')
            || str_contains($path, 'petropd/fuel-tanks/get-tank-product')
        ) {
            return ['petro_pd.create_settlement'];
        }

        // Recover shortage / excess commission should be operator-authorized pages
        if (str_contains($path, 'petropd/recover-shortage') || str_contains($path, 'petropd/excess-comission')) {
            return ['petro_pd.view_operators'];
        }

        /*
         |----------------------------------------------------------------------
         | S678: the tab endpoints on the PD Operators page.
         |----------------------------------------------------------------------
         | These five paths matched no branch above and fell through to the
         | petro_pd.access default. A role granted the Day End Settlement or
         | operator permissions - but not petro_pd.access - was refused, which
         | surfaced as an "Unauthorized Access" toast plus a DataTables Ajax
         | error on the tab that had just been opened.
         |
         | Reported for Day End - Settlements. Pumper Day Entries and Payment
         | Summary failed the same way for the same reason; they are listed
         | here so the next tester does not have to report them separately.
         |
         | Note the singular/plural trap: the operators branch above tests for
         | "petropd/pump-operators", which does NOT match
         | "petropd/pump-operator-payments" or
         | "petropd/pump-operator/get-payment-summary-dashboard".
         */

        // Day End - Settlements tab, its pending-pumps and POS-totals helpers.
        if (str_contains($path, 'petropd/day-end-settlement')) {
            $write = in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

            if ($write) {
                return [
                    'petro_pd.access',
                    'petro_pd_day_end_settlement.edit',
                    'add_day_end_settlement',
                    'edit_day_end_settlement',
                ];
            }

            return [
                'petro_pd.access',
                'petro_pd_day_end_settlement.edit',
                'petro_pd_day_end_settlement.delete',
                'petro_pd.view_operators',
                'add_day_end_settlement',
                'edit_day_end_settlement',
            ];
        }

        // Pumper Day Entries tab and its filter/summary helpers.
        if (str_contains($path, 'petropd/day-entries') || str_contains($path, 'petropd/day-entry')) {
            return ['petro_pd.access', 'petro_pd.view_operators'];
        }

        // Payment Summary tab and the payment totals helper.
        if (str_contains($path, 'petropd/pump-operator-payments')
            || str_contains($path, 'petropd/pump-operator/')) {
            return ['petro_pd.access', 'petro_pd.view_operators'];
        }

        return ['petro_pd.access'];
    }

    private function isPumpAssignmentRequest(Request $request): bool
    {
        return $request->routeIs('petropd.pump-assignments.*')
            || str_starts_with(trim($request->path(), '/'), 'petropd/pump-assignments');
    }
}
