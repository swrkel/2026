<?php

namespace Modules\PetroPDNew\Services;

/**
 * Small optional bridge to the host application's per-business Manage policy.
 *
 * Petro PD-New remains operational when the host does not expose the global
 * Manage utility.  When it is available, disabled pages/tabs are enforced in
 * both rendered navigation and direct controller actions.
 */
class PdnewBusinessFeatureService
{

    private const OPERATOR_TABS = [
        'pump_operators' => 'petro_pd_new_operator_pump_operators',
        'pumper_excess_shortage_payments' => 'petro_pd_new_operator_excess_shortage',
        'pumper_day_entries' => 'petro_pd_new_operator_day_entries',
        'shift_summary' => 'petro_pd_new_operator_shift_summary',
        'payment_summary' => 'petro_pd_new_operator_payment_summary',
        'meters_with_payments' => 'petro_pd_new_operator_meters_with_payments',
        'daily_pump_status' => 'petro_pd_new_operator_daily_pump_status',
        'close_shift' => 'petro_pd_new_operator_close_shift',
        'current_meter' => 'petro_pd_new_operator_current_meter',
        'unload_stock' => 'petro_pd_new_operator_unload_stock',
        'pd_day_end_settlement' => 'petro_pd_new_operator_day_end_settlements',
    ];

    private const SETTLEMENT_TABS = [
        'pumps' => 'petro_pd_new_settlement_pumps',
        'payments' => 'petro_pd_new_settlement_payments',
        'credit' => 'petro_pd_new_settlement_credit_sales',
        'other' => 'petro_pd_new_settlement_other_operations',
        'adjustments' => 'petro_pd_new_settlement_adjustments',
        'reconciliation' => 'petro_pd_new_settlement_reconciliation',
        'documents' => 'petro_pd_new_settlement_documents',
        'history' => 'petro_pd_new_settlement_history',
    ];

    public function enabled(string $key, int $businessId): bool
    {
        $utility = 'App\\Utils\\SidebarPermissionUtil';

        if (! class_exists($utility)
            || ! method_exists($utility, 'isAutomaticPermissionEnabled')) {
            return true;
        }

        try {
            return (bool) $utility::isAutomaticPermissionEnabled($key, $businessId);
        } catch (\Throwable $exception) {
            report($exception);

            // Keep the module usable on installations that do not yet include
            // the global Manage bridge; role permissions still apply normally.
            return true;
        }
    }

    public function authorize(string $key, int $businessId): void
    {
        abort_unless(
            $this->enabled($key, $businessId),
            403,
            'This Petro PD-New page or tab has been disabled from Super Admin Manage for this business.'
        );
    }

    /**
     * @return array<string, bool>
     */
    public function settlementTabs(int $businessId): array
    {
        $result = [];

        foreach (self::SETTLEMENT_TABS as $name => $key) {
            $result[$name] = $this->enabled($key, $businessId);
        }

        return $result;
    }

    public function authorizeSettlementTab(string $name, int $businessId): void
    {
        $key = self::SETTLEMENT_TABS[$name] ?? null;
        abort_unless($key !== null, 404);
        $this->authorize($key, $businessId);
    }


    /**
     * @return array<string, bool>
     */
    public function operatorTabs(int $businessId): array
    {
        $result = [];

        foreach (self::OPERATOR_TABS as $name => $key) {
            $result[$name] = $this->enabled($key, $businessId);
        }

        return $result;
    }

    public function authorizeOperatorTab(string $name, int $businessId): void
    {
        $key = self::OPERATOR_TABS[$name] ?? null;
        abort_unless($key !== null, 404);
        $this->authorize($key, $businessId);
    }

    /**
     * Module-owned navigation catalogue used by both the host ERP sidebar and
     * Petro PD-New's isolated application shell.  Role permissions and the
     * current business's Manage settings are enforced before a link is shown.
     *
     * @param mixed $user
     * @return array<int, array<string, mixed>>
     */
    public function navigationItems(int $businessId, $user): array
    {
        $definitions = [
            [
                'route' => 'petro-pd-new.dashboard',
                'label' => 'Dashboard',
                'icon' => 'fa fa-dashboard',
                'permission' => 'petro_pd_new.dashboard.view',
                'business_key' => 'petro_pd_new_dashboard',
                'active_routes' => ['petro-pd-new.dashboard', 'petro-pd-new.dashboard.index'],
            ],
            [
                'route' => 'petro-pd-new.sources.index',
                'label' => 'PD Closed Shifts',
                'icon' => 'fa fa-exchange',
                'permission' => 'petro_pd_new.sources.view',
                'business_key' => 'petro_pd_new_source_shifts',
                'active_routes' => ['petro-pd-new.sources.*'],
            ],
            [
                'route' => 'petro-pd-new.settlements.index',
                'label' => 'PD Settlements',
                'icon' => 'fa fa-calculator',
                'permission' => 'petro_pd_new.settlements.view',
                'business_key' => 'petro_pd_new_settlements',
                'active_routes' => [
                    'petro-pd-new.settlements.*',
                    'petro-pd-new.payments.*',
                    'petro-pd-new.adjustments.*',
                    'petro-pd-new.workflow.*',
                    'petro-pd-new.reconciliation.*',
                    'petro-pd-new.documents.*',
                ],
            ],
            [
                'route' => 'petro-pd-new.day-ends.index',
                'label' => 'Day End',
                'icon' => 'fa fa-calendar-check-o',
                'permission' => 'petro_pd_new.day_end.view',
                'business_key' => 'petro_pd_new_day_end',
                'active_routes' => ['petro-pd-new.day-ends.*'],
            ],
            [
                'route' => 'petro-pd-new.operators.index',
                'label' => 'PD Operators',
                'icon' => 'fa fa-users',
                'permission' => 'petro_pd_new.operators.view',
                'business_key' => 'petro_pd_new_operators',
                'active_routes' => ['petro-pd-new.operators.*'],
            ],
            [
                'route' => 'petro-pd-new.reports.index',
                'label' => 'Reports',
                'icon' => 'fa fa-bar-chart',
                'permission' => 'petro_pd_new.reports.view',
                'business_key' => 'petro_pd_new_reports',
                'active_routes' => ['petro-pd-new.reports.*'],
            ],
            [
                'route' => 'petro-pd-new.integration.index',
                'label' => 'PD Integration',
                'icon' => 'fa fa-link',
                'permission' => 'petro_pd_new.integration.view',
                'business_key' => 'petro_pd_new_integration',
                'active_routes' => ['petro-pd-new.integration.*'],
            ],
            [
                'route' => 'petro-pd-new.notifications.index',
                'label' => 'Notifications',
                'icon' => 'fa fa-bell',
                'permission' => 'petro_pd_new.notifications.manage',
                'business_key' => 'petro_pd_new_notifications',
                'active_routes' => ['petro-pd-new.notifications.*'],
            ],
            [
                'route' => 'petro-pd-new.audit.index',
                'label' => 'User Activity',
                'icon' => 'fa fa-history',
                'permission' => 'petro_pd_new.audit.view',
                'business_key' => 'petro_pd_new_audit',
                'active_routes' => ['petro-pd-new.audit.*'],
            ],
            [
                'route' => 'petro-pd-new.settings.edit',
                'label' => 'Settings',
                'icon' => 'fa fa-cogs',
                'permission' => 'petro_pd_new.settings.manage',
                'business_key' => 'petro_pd_new_settings',
                'active_routes' => ['petro-pd-new.settings.*'],
            ],
        ];

        $isSuperadmin = $user
            && method_exists($user, 'can')
            && (bool) $user->can('superadmin');

        return array_values(array_filter($definitions, function (array $item) use ($businessId, $user, $isSuperadmin): bool {
            if (! \Illuminate\Support\Facades\Route::has($item['route'])) {
                return false;
            }

            $roleAllowed = $isSuperadmin || (
                $user
                && method_exists($user, 'can')
                && (bool) $user->can($item['permission'])
            );

            return $roleAllowed && $this->enabled($item['business_key'], $businessId);
        }));
    }

    public function reportKey(string $report): string
    {
        return 'petro_pd_new_report_' . preg_replace('/[^a-z0-9_]+/', '_', strtolower($report));
    }

    public function authorizeReport(string $report, int $businessId): void
    {
        $this->authorize($this->reportKey($report), $businessId);
    }

    /**
     * @param array<string, array<string, mixed>> $reports
     * @return array<string, array<string, mixed>>
     */
    public function visibleReports(array $reports, int $businessId): array
    {
        return array_filter(
            $reports,
            fn (array $report, string $key): bool => $this->enabled(
                $this->reportKey($key),
                $businessId
            ),
            ARRAY_FILTER_USE_BOTH
        );
    }
}
