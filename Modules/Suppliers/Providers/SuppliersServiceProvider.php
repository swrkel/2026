<?php

namespace Modules\Suppliers\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Suppliers\Services\Infrastructure\SupplierEventDispatcher;
use Modules\Suppliers\Services\Infrastructure\SupplierMailService;
use Modules\Suppliers\Services\Infrastructure\SupplierNotificationService;
use Modules\Suppliers\Services\Infrastructure\SupplierQueueService;
use Modules\Suppliers\Services\Financial\SupplierAdvanceBankTransferPostingGuard;
use Modules\Suppliers\Services\Financial\SupplierPaymentDateGuard;
use Modules\Suppliers\Services\Financial\SupplierPaymentDoubleEntryGuard;
use App\TransactionPayment as CoreTransactionPayment;
use App\AccountTransaction as CoreAccountTransaction;

class SuppliersServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Suppliers';
    protected string $moduleNameLower = 'suppliers';

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        // Suppliers module standalone assets. Publish with:
        // php artisan vendor:publish --tag=suppliers-assets --force
        $this->publishes([
            module_path($this->moduleName, 'Resources/assets/js') => public_path('modules/suppliers/js'),
            module_path($this->moduleName, 'Resources/assets/css') => public_path('modules/suppliers/css'),
        ], 'suppliers-assets');

        // Supplier payment references are a shared ERP concern. When the
        // plug-and-play runtime is installed, boot it from Suppliers too so
        // Supplier Pay Due remains protected even when Purchase is not the
        // first enabled module/provider on a server. The runtime itself is
        // idempotent, so booting from both modules is safe.
        try {
            if (class_exists(\App\Services\SupplierPaymentReferenceRuntime::class)) {
                app(\App\Services\SupplierPaymentReferenceRuntime::class)->boot();
            }
        } catch (\Throwable $exception) {
            logger()->error('Supplier payment reference runtime could not be booted from Suppliers.', [
                'message' => $exception->getMessage(),
            ]);
        }


        // S717 - Supplier Advance Payment / Bank Transfer integrity.
        //
        // Fix the posting at the source instead of trying to hide a wrong Cash
        // entry later in Finance. The advance-payment modal adds an explicit
        // request marker. On TransactionPayment::saving we propagate the bank
        // account selected by the user before the shared accounting listener
        // can apply its legacy Cash fallback. The saved hooks are a second pass
        // for parent/child allocation rows and for listener-order differences.
        CoreTransactionPayment::saving(function (object $payment): void {
            // S758: preserve the exact Paid on selected in Supplier Pay Due /
            // Advance Payment before shared payment logic can stamp the root row
            // with the current date.
            $this->app->make(SupplierPaymentDateGuard::class)
                ->preparePayment($payment);

            $this->app->make(SupplierAdvanceBankTransferPostingGuard::class)
                ->preparePayment($payment);

            // S763 urgent: remember Supplier Pay Due roots so their final
            // accounting pair can be verified after the shared controller has
            // finished writing its own account_transactions rows.
            $this->app->make(SupplierPaymentDoubleEntryGuard::class)
                ->capturePayment($payment);
        });

        CoreTransactionPayment::saved(function (object $payment): void {
            // S759: a later shared listener must not replace the user's selected
            // Paid on value with the current timestamp after the saving event.
            $this->app->make(SupplierPaymentDateGuard::class)
                ->normalizePayment($payment);

            $this->app->make(SupplierAdvanceBankTransferPostingGuard::class)
                ->normalizePayment($payment);

            $this->app->make(SupplierPaymentDoubleEntryGuard::class)
                ->capturePayment($payment);
        });

        CoreAccountTransaction::saved(function (object $accountTransaction): void {
            // S763: the Finance ledger rows must use the exact Supplier payment
            // date selected by the user, not the model/system creation date.
            $this->app->make(SupplierPaymentDateGuard::class)
                ->normalizeAccountTransaction($accountTransaction);

            $this->app->make(SupplierAdvanceBankTransferPostingGuard::class)
                ->normalizeAccountTransaction($accountTransaction);

            $this->app->make(SupplierPaymentDoubleEntryGuard::class)
                ->normalizeAccountTransaction($accountTransaction);
        });

        // Do not race the shared payment controller. It writes the Supplier
        // payment rows after TransactionPayment::create(), so verify/create a
        // missing DR/CR leg only when the request is terminating.
        $this->app->terminating(function (): void {
            // Complete the DR/CR pair first, then make the selected Supplier
            // payment date authoritative across the root payment, allocation
            // children and all linked Finance rows. Allocation children are
            // bulk-inserted by the shared controller and therefore do not fire
            // TransactionPayment model events themselves.
            $this->app->make(SupplierPaymentDoubleEntryGuard::class)->flushPending();
            $this->app->make(SupplierPaymentDateGuard::class)->flushPending();
        });

    }

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower);

        $this->app->singleton(SupplierNotificationService::class);
        $this->app->singleton(SupplierQueueService::class);
        $this->app->singleton(SupplierEventDispatcher::class);
        $this->app->singleton(SupplierMailService::class);
        $this->app->singleton(SupplierAdvanceBankTransferPostingGuard::class);
        $this->app->singleton(SupplierPaymentDateGuard::class);
        $this->app->singleton(SupplierPaymentDoubleEntryGuard::class);
    }
}
