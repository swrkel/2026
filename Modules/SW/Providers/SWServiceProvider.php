<?php

namespace Modules\SW\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Modules\SW\Entities\Shift;

class SWServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('SW', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('SW', 'Resources/views'), 'sw');
        $this->loadTranslationsFrom(module_path('SW', 'Resources/lang'), 'sw');
        $this->mergeConfigFrom(module_path('SW', 'Config/config.php'), 'sw');

        /*
         | IS2267 - Finance Cash/Card Deposit SW shift source.
         |
         | The Finance deposit modal does not render sw::partials.shift_field.
         | It owns a different field named daily_shift_no and historically feeds
         | it from PetroDailyShift.  Earlier SW-only fixes therefore never
         | touched the control shown in Finance / List Accounts / Cash Deposit
         | or Card Deposit.
         |
         | A view composer is the safest standalone bridge: when Finance renders
         | its deposit modal, replace ONLY the existing $petroDailyShifts view
         | variable with the live OPEN sw_shifts for the active business.  No
         | Finance controller/view file is modified and no posting logic changes.
        */
        $this->registerFinanceDepositShiftComposer();

        /*
         | IS2269 - persist Finance Cash Deposit -> Daily Shift No.
         |
         | Finance's deposit form posts the selected SW value as daily_shift_no,
         | but its AccountController historically never copied that value into
         | account_transactions. The Daily Cash Status therefore had nothing to
         | match even though the user selected the correct SW shift on screen.
         |
         | Keep this integration standalone: observe only the Finance cash-deposit
         | account row and attach the chosen SW shift to account_transactions.
         | No Finance source file is modified.
        */
        $this->registerFinanceCashDepositShiftPersistenceBridge();

        /*
         | IS2256 - Petro General Tank Transaction integration.
         |
         | Attach the SW report augmenter only after a Petro tank report route
         | is matched. Appending it at route-match time keeps it inside the
         | tenant middleware stack and avoids a global middleware that would run
         | on every ERP request.
        */
        $this->app['router']->aliasMiddleware(
            'sw.petro-tank-report',
            \Modules\SW\Http\Middleware\AugmentPetroTankTransactions::class
        );

        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            $action = (string) $event->route->getActionName();
            if (strpos($action, 'TanksTransactionDetailController@') === false) {
                return;
            }

            if (! str_ends_with($action, 'TanksTransactionDetailController@index')
                && ! str_ends_with($action, 'TanksTransactionDetailController@tankTransactionSummary')) {
                return;
            }

            $middleware = (array) ($event->route->getAction('middleware') ?: []);
            if (! in_array('sw.petro-tank-report', $middleware, true)) {
                $event->route->middleware('sw.petro-tank-report');
            }
        });

        /*
         | Shift-options compatibility safety net.
         |
         | Some older SW route files did not contain sw.shift-options while
         | core forms already included sw::partials.shift_field. Rendering the
         | Blade then failed before the page could open. The primary route is
         | defined in Routes/web.php; this fallback only registers when an
         | older/cached route set does not contain it.
        */
        $this->app->booted(function () {
            if (! Route::has('sw.shift-options')) {
                Route::group([
                    'middleware' => ['web', 'tenant.context', 'auth', 'SetSessionData', 'language', 'timezone'],
                ], function () {
                    Route::get('/sw/shift-options', [\Modules\SW\Http\Controllers\ShiftOptionController::class, 'index'])
                        ->name('sw.shift-options');
                });
            }
        });

        /*
         | Compatibility for the retiring SettlementSW menu links.
         |
         | Some existing sidebar/Add links still point to /settlement-sw/create.
         | Register this alias only after every
         | provider has booted, so the old module cannot win the same URI by
         | load order and send the user back to the retired account-type table.
        */
        $this->app->booted(function () {
            Route::group([
                'middleware' => ['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'],
            ], function () {
                Route::get('/settlement-sw/create', function () {
                    return redirect()->route('sw.settlements.create', request()->query());
                })->name('sw.legacy-settlement.create');
            });
        });
    }


    /**
     * Feed Finance's existing Daily Shift No field from SW's authoritative
     * shift table. The composer executes while the modal is rendered, after
     * tenant/context middleware has selected the correct tenant database.
     */
    protected function registerFinanceDepositShiftComposer(): void
    {
        View::composer([
            'finance::account.deposit',
            // Compatibility with installations that still render the host
            // (non-namespaced) account deposit view.
            'account.deposit',
        ], function ($view) {
            $businessId = (int) (
                session('user.business_id')
                ?: session('business.id')
                ?: (optional(auth()->user())->business_id ?? 0)
            );

            if ($businessId <= 0 || ! $this->liveTableExists('sw_shifts')) {
                return;
            }

            // Respect Manage Sidebar. If SW is disabled for this business,
            // leave Finance's original Petro Daily Shift list untouched.
            if (! class_exists(\App\Utils\SidebarPermissionUtil::class)) {
                return;
            }

            try {
                if (! \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('sw', $businessId)) {
                    return;
                }
            } catch (\Throwable $e) {
                // Never allow an SW integration check to break a Finance modal.
                return;
            }

            try {
                $hasClosedAt = $this->liveHasColumn('sw_shifts', 'closed_at');
                $query = DB::table('sw_shifts')
                    ->where('business_id', $businessId);

                if ($this->liveHasColumn('sw_shifts', 'deleted_at')) {
                    $query->whereNull('deleted_at');
                }

                $columns = ['id', 'sw_shift_no', 'status'];
                if ($hasClosedAt) {
                    $columns[] = 'closed_at';
                }

                $shiftNumbers = $query
                    ->orderByDesc('id')
                    ->get($columns)
                    ->filter(function ($row) use ($hasClosedAt) {
                        return Shift::normalizeStatusValue(
                            $row->status ?? null,
                            $hasClosedAt ? ($row->closed_at ?? null) : null
                        ) === Shift::STATUS_OPEN;
                    })
                    ->pluck('sw_shift_no')
                    ->map(static fn ($value) => trim((string) $value))
                    ->filter(static fn ($value) => $value !== '')
                    ->unique()
                    ->values()
                    ->all();

                // Finance's current deposit Blade expects this exact variable.
                // Keep the singular alias as a compatibility aid for old builds.
                $view->with('petroDailyShifts', $shiftNumbers);
                $view->with('petroDailyShift', $shiftNumbers);
            } catch (\Throwable $e) {
                // Preserve the host Finance modal if a tenant has an unexpected
                // legacy schema. SW integration must never make Finance unusable.
                return;
            }
        });
    }

    /**
     * Persist the SW Daily Shift No selected on Finance Cash Deposit.
     *
     * Finance creates two account_transactions rows for a deposit. Only the Cash
     * account leg belongs to SW Daily Cash Status; linking both legs would double
     * the deposit in Balance In Hand. The listener therefore tags only the row
     * whose account is the business Cash account and whose sub_type is deposit.
     *
     * The saving event (not only creating) is intentional: Finance can reuse an
     * existing duplicate-detected credit row and save it again after pairing the
     * transfer transaction. In that case a creating-only listener would never run.
     */
    protected function registerFinanceCashDepositShiftPersistenceBridge(): void
    {
        $this->app->booted(function () {
            $models = array_unique([
                'Modules\\Finance\\Entities\\AccountTransaction',
                'App\\AccountTransaction',
            ]);

            foreach ($models as $modelClass) {
                if (! class_exists($modelClass)) {
                    continue;
                }

                try {
                    $modelClass::saving(function ($model) {
                        try {
                            if (method_exists($model, 'getTable')
                                && $model->getTable() !== 'account_transactions') {
                                return;
                            }

                            $request = request();
                            $route = $request->route();
                            $routeName = is_object($route) && method_exists($route, 'getName')
                                ? (string) $route->getName()
                                : '';
                            $actionName = is_object($route) && method_exists($route, 'getActionName')
                                ? (string) $route->getActionName()
                                : '';

                            $isFinanceDepositStore = str_ends_with($routeName, 'account.deposit.store')
                                || str_contains($actionName, 'AccountController@postDeposit');

                            if (! $isFinanceDepositStore) {
                                return;
                            }

                            $shiftNo = trim((string) (
                                $request->input('daily_shift_no')
                                ?: $request->input('sw_shift_no')
                                ?: ''
                            ));

                            if ($shiftNo === ''
                                || ! $this->liveTableExists('sw_shifts')
                                || ! $this->liveHasColumn('account_transactions', 'sw_shift_no')) {
                                return;
                            }

                            $businessId = (int) (
                                $model->getAttribute('business_id')
                                ?: session('user.business_id')
                                ?: session('business.id')
                                ?: (optional(auth()->user())->business_id ?? 0)
                            );

                            if ($businessId <= 0) {
                                return;
                            }

                            // Never accept a shift number from another business or a
                            // shift that became closed while the Finance modal was open.
                            $hasClosedAt = $this->liveHasColumn('sw_shifts', 'closed_at');
                            $shiftQuery = DB::table('sw_shifts')
                                ->where('business_id', $businessId)
                                ->where('sw_shift_no', $shiftNo);

                            if ($this->liveHasColumn('sw_shifts', 'deleted_at')) {
                                $shiftQuery->whereNull('deleted_at');
                            }

                            $shiftColumns = ['status'];
                            if ($hasClosedAt) {
                                $shiftColumns[] = 'closed_at';
                            }

                            $shift = $shiftQuery->first($shiftColumns);
                            if (! $shift
                                || Shift::normalizeStatusValue(
                                    $shift->status ?? null,
                                    $hasClosedAt ? ($shift->closed_at ?? null) : null
                                ) !== Shift::STATUS_OPEN) {
                                return;
                            }

                            if (strtolower(trim((string) $model->getAttribute('sub_type'))) !== 'deposit') {
                                return;
                            }

                            // Finance's Cash Deposit form uses the account named Cash.
                            // This excludes Card Deposit and the receiving Bank leg.
                            $accountId = (int) $model->getAttribute('account_id');
                            if ($accountId <= 0) {
                                return;
                            }

                            $isCashLeg = DB::table('accounts')
                                ->where('id', $accountId)
                                ->where('business_id', $businessId)
                                ->whereRaw('LOWER(TRIM(name)) = ?', ['cash'])
                                ->exists();

                            if (! $isCashLeg) {
                                return;
                            }

                            $model->setAttribute('sw_shift_no', $shiftNo);
                        } catch (\Throwable $e) {
                            // The bridge must never make Finance deposits unusable.
                            return;
                        }
                    });
                } catch (\Throwable $e) {
                    // A tenant may use only one of the two historical model classes.
                    continue;
                }
            }
        });
    }

    /** Inspect the tenant database selected for the current request. */
    protected function liveTableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT 1 FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    protected function liveHasColumn(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT `' . $column . '` FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
