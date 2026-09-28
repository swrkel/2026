<?php

namespace Modules\Finance\Providers;

use App\AccountTransaction as CoreAccountTransaction;
use App\TransactionPayment as CoreTransactionPayment;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Finance\Entities\AccountTransaction as FinanceAccountTransaction;
use Modules\Finance\Http\Middleware\InitializeFinanceTenantContext;
use Modules\Finance\Services\Purchases\PurchaseFinishedGoodsDebitNormalizer;
use Modules\Finance\Services\Payments\SupplierAdvancePaymentAccountNormalizer;
use Modules\Finance\Console\Commands\RepairSupplierAdvancePaymentAccounts;
use Modules\Finance\Console\Commands\SyncSuperadminAccountNumbers;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;

class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Register Finance-owned views/translations and load Finance routes after
     * all legacy application and tenant route files have been registered.
     *
     * The application contains duplicate /accounting-module URLs in root route
     * files. Loading the module routes last makes Laravel resolve the Finance
     * controllers and views without changing the long-standing public URLs.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'finance');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'finance');


        /*
         * List Accounts legacy-URL rescue.
         *
         * Route-cached installations can keep an old/core route for
         * /finance/account or /accounting-module/account even after the Finance
         * module is replaced. If that stale route deliberately returns 404,
         * normal late route registration cannot always override the compiled
         * matcher. Rescue only these exact historical GET/HEAD URLs and redirect
         * them to Finance's collision-proof List Accounts URI. The destination
         * keeps the normal auth/session/permission middleware, so this does not
         * bypass access control.
         */
        try {
            $handler = $this->app->make(ExceptionHandlerContract::class);
            if (method_exists($handler, 'renderable')) {
                $handler->renderable(function (NotFoundHttpException $e, Request $request) {
                    if (! in_array(strtoupper($request->method()), ['GET', 'HEAD'], true)) {
                        return null;
                    }

                    $path = '/' . ltrim($request->path(), '/');
                    if (! in_array($path, [
                        '/finance/account',
                        '/finance/accounts',
                        '/finance/list-accounts',
                        '/accounting-module/account',
                        '/accounting-module/accounts',
                    ], true)) {
                        return null;
                    }

                    return redirect()->to(url('/finance/list-accounts-live'));
                });
            }
        } catch (\Throwable $e) {
            // Never let an optional compatibility rescue prevent Finance boot.
        }

        // Register Finance-owned migrations so the Bank Reconciliation tables
        // are available to the application's normal migration workflow.
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Finance-owned integration hook: any purchase posting written to the
        // Finished Goods account is persisted as a debit. No Purchase module
        // controller, model, route, view, JavaScript or service is modified.
        //
        // MA-002: this hook was registered on the CORE AccountTransaction only.
        // Finance also has its own AccountTransaction entity over the same
        // `account_transactions` table, and JournalController and
        // FixedAssetController already write through it - those saves were
        // bypassing the normalizer entirely.
        //
        // Registering on both classes makes the Finished-Goods-is-a-debit
        // invariant hold no matter which model performed the save, and makes
        // it safe to repoint the remaining Finance controllers onto the
        // module's own entities.
        //
        // The normalizer is idempotent: it returns immediately when the row is
        // already a debit, and it writes via the query builder, so it cannot
        // trigger itself recursively.
        $normalizeFinishedGoodsPurchase = function (object $accountTransaction): void {
            $this->app->make(PurchaseFinishedGoodsDebitNormalizer::class)
                ->normalize($accountTransaction);
        };

        CoreAccountTransaction::saved($normalizeFinishedGoodsPurchase);
        FinanceAccountTransaction::saved($normalizeFinishedGoodsPurchase);

        // S712: supplier Advance Payment made by Bank Transfer must never be
        // posted to the Cash account merely because a legacy caller omitted
        // account_id. Supplier payments are created outside Finance, therefore
        // listen to BOTH core and Finance account-transaction models and repair
        // only the narrowly identifiable Cash-fallback case.
        $normalizeSupplierAdvancePaymentAccount = function (object $accountTransaction): void {
            $this->app->make(SupplierAdvancePaymentAccountNormalizer::class)
                ->normalize($accountTransaction);
        };

        CoreAccountTransaction::saved($normalizeSupplierAdvancePaymentAccount);
        FinanceAccountTransaction::saved($normalizeSupplierAdvancePaymentAccount);

        // S717 source-side protection: when the Supplier Advance Payment form
        // explicitly identifies itself, put the selected bank account on the
        // TransactionPayment BEFORE shared account listeners can fall back to
        // Cash. Suppliers has the same standalone guard; keeping this small
        // Finance hook makes the accounting invariant hold regardless of
        // provider/listener ordering.
        CoreTransactionPayment::saving(function (object $payment): void {
            $this->app->make(SupplierAdvancePaymentAccountNormalizer::class)
                ->preparePayment($payment);
        });

        // Some supplier-payment flows assign the selected account_id to the
        // TransactionPayment after the account row has already been created.
        // Re-check linked rows after the payment save as well, so the posting
        // cannot remain in Cash because of event ordering.
        CoreTransactionPayment::saved(function (object $payment): void {
            $paymentId = (int) ($payment->id ?? 0);
            if ($paymentId > 0) {
                $this->app->make(SupplierAdvancePaymentAccountNormalizer::class)
                    ->normalizePaymentId($paymentId);
            }
        });

        $this->app->booted(function (): void {
            /*
             * Register the Finance-owned routes even when Laravel currently has
             * a generated route cache.  This module is deployed plug-and-play
             * across many servers; one server may still have an older cached
             * Finance route whose tenant middleware returns 404.  Registering
             * the current module routes last makes the installed module the
             * authoritative runtime route set immediately, without requiring a
             * non-technical user to run route:clear/optimize:clear.
             *
             * The application binding below still prevents duplicate loading
             * within the same request.
             */
            if ($this->app->bound('finance.routes.loaded.after.application')) {
                return;
            }

            $this->app->instance('finance.routes.loaded.after.application', true);

            /*
             * Do not use PreventAccessFromCentralDomains here.
             *
             * This ERP supports business users operating on either a tenant
             * domain or the configured central host. The old middleware stack
             * deliberately returned 404 for every Finance route on the central
             * host, which affected List Accounts, Disabled Accounts, Journals,
             * reports, Fixed Assets, Cash Flow and Import Accounts.
             *
             * InitializeFinanceTenantContext is route-cache safe: it leaves a
             * recognized central host unchanged and initializes Stancl tenancy
             * only for an actual tenant/custom domain.
             */
            Route::middleware(['web', InitializeFinanceTenantContext::class])
                ->group(function (): void {
                    require __DIR__ . '/../Routes/web.php';
                });

            // RouteCollection caches URI/name/action lookups. Refresh them after
            // the final Finance routes replace duplicate legacy URIs.
            $routes = Route::getRoutes();
            if (method_exists($routes, 'refreshNameLookups')) {
                $routes->refreshNameLookups();
            }
            if (method_exists($routes, 'refreshActionLookups')) {
                $routes->refreshActionLookups();
            }
        });
    }

    public function register(): void
    {
        $this->app->singleton(PurchaseFinishedGoodsDebitNormalizer::class);
        $this->app->singleton(SupplierAdvancePaymentAccountNormalizer::class);

        $this->commands([
            RepairSupplierAdvancePaymentAccounts::class,
            SyncSuperadminAccountNumbers::class,
        ]);
    }
}
