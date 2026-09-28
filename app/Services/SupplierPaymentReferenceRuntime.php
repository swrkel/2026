<?php

namespace App\Services;

use App\AccountTransaction;
use App\ContactLedger;
use App\TransactionPayment;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class SupplierPaymentReferenceRuntime
{
    private static bool $booted = false;

    /** @var array<string, array<int, string>> */
    private static array $pendingSlpParents = [];

    private static bool $normalisingChildren = false;

    public function __construct(private SupplierPaymentReferenceService $references)
    {
    }

    public function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;

        try {
            $this->references->setCentralDatabase((string) DB::connection()->getDatabaseName());
        } catch (\Throwable $e) {
            // A later tenant view/payment can still initialise normally.
        }

        $this->registerAutomaticHistoricalConversion();
        $this->registerSupplierPayDuePreview();
        $this->registerSupplierPayDueSaveHook();
        $this->registerLedgerReferenceHooks();
        $this->registerAllocationNormaliser();
    }

    private function registerAutomaticHistoricalConversion(): void
    {
        // Runs on the first normal rendered page for each tenant after upload.
        // Central/master DB is skipped by SupplierPaymentReferenceService.
        View::composer('*', function (): void {
            try {
                if (DB::transactionLevel() === 0) {
                    $this->references->ensureHistoricalBackfill();
                }
            } catch (\Throwable $e) {
                Log::error('Supplier payment reference plug-and-play conversion check failed.', [
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    private function registerSupplierPayDuePreview(): void
    {
        View::composer('transaction_payment.pay_supplier_due_modal', function ($view): void {
            try {
                $data = $view->getData();
                if (($data['due_payment_type'] ?? null) !== 'purchase') {
                    return;
                }

                $contactId = (int) data_get($data, 'contact_details.contact_id', 0);
                if ($contactId <= 0 || ! Schema::hasTable('contacts')) {
                    return;
                }

                $type = (string) DB::table('contacts')->where('id', $contactId)->value('type');
                if (! in_array($type, ['supplier', 'both'], true)) {
                    return;
                }

                $businessId = (int) (
                    session('user.business_id')
                    ?: session('business.id')
                    ?: (auth()->check() ? (auth()->user()->business_id ?? 0) : 0)
                );
                if ($businessId <= 0) {
                    return;
                }

                $paidOn = data_get($data, 'payment_line.paid_on', now());
                $view->with('payment_ref_no', $this->references->preview('SLP', $businessId, $paidOn));
            } catch (\Throwable $e) {
                Log::error('Supplier payment SLP preview could not be prepared.', [
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    private function registerSupplierPayDueSaveHook(): void
    {
        if (! class_exists(TransactionPayment::class)) {
            return;
        }

        TransactionPayment::creating(function (TransactionPayment $payment): void {
            try {
                if (! request() || request()->input('due_payment_type') !== 'purchase') {
                    return;
                }
                if (! empty($payment->parent_id) || ! empty($payment->transaction_id)) {
                    return;
                }
                if ((string) ($payment->paid_in_type ?? '') !== 'customer_page') {
                    return;
                }

                $contactId = (int) ($payment->payment_for ?? 0);
                if ($contactId <= 0 || ! Schema::hasTable('contacts')) {
                    return;
                }

                $type = (string) DB::table('contacts')->where('id', $contactId)->value('type');
                if (! in_array($type, ['supplier', 'both'], true)) {
                    return;
                }

                if ($this->references->isSystemReference((string) ($payment->payment_ref_no ?? ''), 'SLP')) {
                    return;
                }

                $businessId = (int) ($payment->business_id ?? 0);
                $payment->payment_ref_no = $this->references->next('SLP', $businessId, $payment->paid_on ?? now());
            } catch (\Throwable $e) {
                // Saving without a permanent system reference is not acceptable.
                throw $e;
            }
        });

        TransactionPayment::created(function (TransactionPayment $payment): void {
            $reference = trim((string) ($payment->payment_ref_no ?? ''));
            if (! $this->references->isSystemReference($reference, 'SLP')) {
                return;
            }
            if (! empty($payment->parent_id) || ! empty($payment->transaction_id)) {
                return;
            }

            $connection = (string) ($payment->getConnectionName() ?: DB::getDefaultConnection());
            self::$pendingSlpParents[$connection][(int) $payment->id] = $reference;
        });
    }

    private function registerLedgerReferenceHooks(): void
    {
        if (class_exists(AccountTransaction::class)) {
            AccountTransaction::creating(function (AccountTransaction $row): void {
                $this->copyPaymentReferenceToLedgerRow($row);
            });
        }

        if (class_exists(ContactLedger::class)) {
            ContactLedger::creating(function (ContactLedger $row): void {
                $this->copyPaymentReferenceToLedgerRow($row);
            });
        }
    }

    private function copyPaymentReferenceToLedgerRow($row): void
    {
        try {
            $paymentId = (int) ($row->transaction_payment_id ?? 0);
            if ($paymentId <= 0 || ! Schema::hasTable('transaction_payments')) {
                return;
            }

            $reference = (string) DB::table('transaction_payments')
                ->where('id', $paymentId)
                ->value('payment_ref_no');

            if ($this->references->isSystemReference($reference)) {
                $row->reff_no = $reference;
            }
        } catch (\Throwable $e) {
            Log::error('Supplier payment reference could not be copied to ledger/account-book row.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function registerAllocationNormaliser(): void
    {
        DB::listen(function (QueryExecuted $event): void {
            if (self::$normalisingChildren || self::$pendingSlpParents === []) {
                return;
            }

            if (! preg_match('/^\\s*insert\\s+into\\s+[`\"]?transaction_payments[`\"]?/i', $event->sql)) {
                return;
            }

            $connection = (string) ($event->connectionName ?: DB::getDefaultConnection());
            $pending = self::$pendingSlpParents[$connection] ?? [];
            if ($pending === []) {
                return;
            }

            self::$normalisingChildren = true;
            try {
                foreach ($pending as $parentId => $reference) {
                    $query = $event->connection->table('transaction_payments')
                        ->where('parent_id', (int) $parentId);
                    if (Schema::connection($connection)->hasColumn('transaction_payments', 'deleted_at')) {
                        $query->whereNull('deleted_at');
                    }
                    $updated = $query->update(['payment_ref_no' => $reference]);
                    if ($updated > 0) {
                        unset(self::$pendingSlpParents[$connection][$parentId]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Supplier Pay Due child references could not be normalised.', [
                    'message' => $e->getMessage(),
                ]);
            } finally {
                self::$normalisingChildren = false;
            }
        });
    }
}
