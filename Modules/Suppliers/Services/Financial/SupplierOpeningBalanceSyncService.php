<?php

namespace Modules\Suppliers\Services\Financial;

use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierAccountTransaction;
use Modules\Suppliers\Entities\SupplierTransaction;
use Modules\Suppliers\Utils\SupplierContextUtil;

/**
 * Keeps the standalone Suppliers module opening balance in the same canonical
 * format used by the core Contact/Add Supplier flow.
 *
 * Older standalone Supplier records may contain contacts.opening_balance only.
 * The Supplier list can display that amount through its fallback calculation,
 * but the shared Pay Due flow allocates against transactions.  This adapter
 * therefore creates/updates the normal transactions.type=opening_balance row
 * (and the standard accounting/ledger entries through TransactionUtil).
 */
class SupplierOpeningBalanceSyncService
{
    public function __construct(private TransactionUtil $transactionUtil)
    {
    }

    public function sync(Supplier $supplier, ?string $transactionDate = null): ?int
    {
        $businessId = (int) SupplierContextUtil::businessId();

        if ((int) $supplier->business_id !== $businessId || ! in_array((string) $supplier->type, ['supplier', 'both'], true)) {
            return null;
        }

        return DB::transaction(function () use ($supplier, $businessId, $transactionDate): ?int {
            $columns = ['id', 'business_id', 'opening_balance', 'created_at'];
            if (Schema::hasColumn('contacts', 'transaction_date')) {
                $columns[] = 'transaction_date';
            }

            $contact = DB::table('contacts')
                ->where('business_id', $businessId)
                ->where('id', (int) $supplier->id)
                ->lockForUpdate()
                ->first($columns);

            if (! $contact) {
                return null;
            }

            $amount = (float) ($contact->opening_balance ?? 0);

            /** @var SupplierTransaction|null $openingTransaction */
            $openingTransaction = SupplierTransaction::query()
                ->where('business_id', $businessId)
                ->where('contact_id', (int) $supplier->id)
                ->where('type', 'opening_balance')
                ->orderBy('id')
                ->first();

            if ($openingTransaction) {
                $this->updateCanonicalTransaction($openingTransaction, $amount);

                return (int) $openingTransaction->id;
            }

            // Zero opening balance requires no transaction.  This mirrors the
            // established ContactController behaviour for newly added contacts.
            if (abs($amount) < 0.000001) {
                return null;
            }

            $effectiveDate = $transactionDate;
            if (empty($effectiveDate) && property_exists($contact, 'transaction_date')) {
                $effectiveDate = $contact->transaction_date;
            }
            if (empty($effectiveDate) && ! empty($contact->created_at)) {
                $effectiveDate = $contact->created_at;
            }

            // Use the ERP's established opening-balance posting routine so AP,
            // Opening Balance Equity and Contact Ledger remain consistent with
            // suppliers created through the core Contact module.
            $this->transactionUtil->createOpeningBalanceTransaction(
                $businessId,
                (int) $supplier->id,
                $amount,
                $effectiveDate
            );

            $created = SupplierTransaction::query()
                ->where('business_id', $businessId)
                ->where('contact_id', (int) $supplier->id)
                ->where('type', 'opening_balance')
                ->orderByDesc('id')
                ->first();

            return $created ? (int) $created->id : null;
        }, 3);
    }

    private function updateCanonicalTransaction(SupplierTransaction $transaction, float $amount): void
    {
        $transaction->final_total = $amount;
        $transaction->total_before_tax = $amount;
        $transaction->save();

        // Match the established ContactController edit path: keep the original
        // transaction date, update AP and Contact Ledger amounts, then recalc the
        // payment status from the payments already allocated to this OB row.
        $payableAccountId = (int) $this->transactionUtil->account_exist_return_id('Accounts Payable');
        if ($payableAccountId <= 0) {
            $payableAccountId = (int) $this->transactionUtil->account_exist_return_id('Account Payable');
        }

        if ($payableAccountId > 0) {
            SupplierAccountTransaction::query()
                ->where('transaction_id', (int) $transaction->id)
                ->where('account_id', $payableAccountId)
                ->update(['amount' => $amount]);
        }

        if (Schema::hasTable('contact_ledgers')) {
            $ledgerQuery = DB::table('contact_ledgers')
                ->where('transaction_id', (int) $transaction->id);

            if (Schema::hasColumn('contact_ledgers', 'deleted_at')) {
                $ledgerQuery->whereNull('deleted_at');
            }

            $ledgerQuery->update(['amount' => $amount]);
        }

        $this->transactionUtil->updatePaymentStatus((int) $transaction->id, $amount);
    }
}
