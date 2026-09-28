<?php

namespace Modules\MembershipNew\app\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MembershipNewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(module_path('MembershipNew', 'resources/views'), 'membershipnew');

        /*
         * The ERP main sidebar calls:
         * @includeIf('layouts.partials.sidebar-sections.sidebar-membership-new')
         *
         * Some installations already contain an older copy of that host view in
         * resources/views.  If the module path is only appended, Laravel resolves
         * the old host copy first and newly-added page links (Members, Dashboard,
         * Plans, Payments, Settings, etc.) never appear.  Prepend the module-owned
         * host view location so the installed Membership-New version is the single
         * current source while still leaving the global/core sidebar untouched.
         */
        $hostViewsPath = module_path('MembershipNew', 'resources/host_views');
        if (is_dir($hostViewsPath)) {
            try {
                $finder = View::getFinder();

                // Force the module-owned host bridge to the front.  Some ERP
                // installations already contain an older global copy of
                // sidebar-membership-new.blade.php.  Merely appending a location
                // leaves that stale copy in control.
                if (method_exists($finder, 'prependLocation')) {
                    $finder->prependLocation($hostViewsPath);
                } elseif (method_exists($finder, 'getPaths') && method_exists($finder, 'setPaths')) {
                    $paths = array_values(array_unique(array_merge([$hostViewsPath], $finder->getPaths())));
                    $finder->setPaths($paths);
                } else {
                    View::addLocation($hostViewsPath);
                }

                // FileViewFinder caches resolved view names for the lifetime of
                // the request.  Flush that cache after changing path priority so
                // an earlier resolution of the legacy host partial cannot win.
                if (method_exists($finder, 'flush')) {
                    $finder->flush();
                }
            } catch (\Throwable $e) {
                View::addLocation($hostViewsPath);
                try {
                    $finder = View::getFinder();
                    if (method_exists($finder, 'flush')) {
                        $finder->flush();
                    }
                } catch (\Throwable $ignored) {
                    // No-op: the normal global view path remains a safe fallback.
                }
            }
        }

        $this->loadTranslationsFrom(module_path('MembershipNew', 'resources/lang'), 'membershipnew');
        $this->loadMigrationsFrom(module_path('MembershipNew', 'database/migrations'));

        // Membership-New owns creator tracking for its records. This keeps Added By
        // available consistently without requiring every controller/service to repeat
        // created_by assignment logic. Existing explicit created_by values are preserved.
        $this->registerCreatorTracking();

        $this->publishes([
            module_path('MembershipNew', 'public') => public_path('modules/membershipnew'),
        ], 'public');
    }

    private function registerCreatorTracking(): void
    {
        $models = [
            \Modules\MembershipNew\app\Models\MembershipNewApprovalRequest::class,
            \Modules\MembershipNew\app\Models\MembershipNewAuditLog::class,
            \Modules\MembershipNew\app\Models\MembershipNewBusinessAccessRule::class,
            \Modules\MembershipNew\app\Models\MembershipNewBusinessCustomerHistory::class,
            \Modules\MembershipNew\app\Models\MembershipNewCentralMember::class,
            \Modules\MembershipNew\app\Models\MembershipNewCustomerMap::class,
            \Modules\MembershipNew\app\Models\MembershipNewDividendBatch::class,
            \Modules\MembershipNew\app\Models\MembershipNewDividendPayment::class,
            \Modules\MembershipNew\app\Models\MembershipNewDividendPayout::class,
            \Modules\MembershipNew\app\Models\MembershipNewDuplicateCandidate::class,
            \Modules\MembershipNew\app\Models\MembershipNewErrorLog::class,
            \Modules\MembershipNew\app\Models\MembershipNewIdentityCard::class,
            \Modules\MembershipNew\app\Models\MembershipNewImportBatch::class,
            \Modules\MembershipNew\app\Models\MembershipNewLinkedBusiness::class,
            \Modules\MembershipNew\app\Models\MembershipNewMember::class,
            \Modules\MembershipNew\app\Models\MembershipNewMemberBusinessMap::class,
            \Modules\MembershipNew\app\Models\MembershipNewMergeRequest::class,
            \Modules\MembershipNew\app\Models\MembershipNewOutletTransactionQueue::class,
            \Modules\MembershipNew\app\Models\MembershipNewPayment::class,
            \Modules\MembershipNew\app\Models\MembershipNewPlan::class,
            \Modules\MembershipNew\app\Models\MembershipNewPointRule::class,
            \Modules\MembershipNew\app\Models\MembershipNewPointTransaction::class,
            \Modules\MembershipNew\app\Models\MembershipNewRegion::class,
            \Modules\MembershipNew\app\Models\MembershipNewSettingOption::class,
            \Modules\MembershipNew\app\Models\MembershipNewShareHolding::class,
        ];

        $columnCache = [];

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            $modelClass::creating(function ($model) use (&$columnCache) {
                $table = $model->getTable();
                $key = $table . ':created_by';
                if (!array_key_exists($key, $columnCache)) {
                    try {
                        $columnCache[$key] = Schema::hasTable($table) && Schema::hasColumn($table, 'created_by');
                    } catch (\Throwable $e) {
                        $columnCache[$key] = false;
                    }
                }

                if ($columnCache[$key] && empty($model->created_by) && auth()->check()) {
                    $model->created_by = auth()->id();
                }
            });

            $modelClass::updating(function ($model) use (&$columnCache) {
                $table = $model->getTable();
                $key = $table . ':updated_by';
                if (!array_key_exists($key, $columnCache)) {
                    try {
                        $columnCache[$key] = Schema::hasTable($table) && Schema::hasColumn($table, 'updated_by');
                    } catch (\Throwable $e) {
                        $columnCache[$key] = false;
                    }
                }

                if ($columnCache[$key] && auth()->check()) {
                    $model->updated_by = auth()->id();
                }
            });
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(module_path('MembershipNew', 'config/config.php'), 'membershipnew');
    }
}
