<?php

namespace Modules\PetroDirect\Services;

use App\AccountTransaction;
use App\ContactLedger;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\PetroDirect\Entities\DailyCollection;
use Modules\PetroDirect\Entities\DailyVoucher;
use Modules\PetroDirect\Entities\PumpOperatorPayment;
use Modules\PetroDirect\Entities\SettlementCardPayment;
use Modules\PetroDirect\Entities\SettlementCashPayment;
use Modules\PetroDirect\Entities\SettlementChequePayment;
use Modules\PetroDirect\Entities\SettlementCreditSalePayment;

class SettlementPaymentEditService
{
    public function editCreditSale(int $businessId, int $scspId, array $data): SettlementCreditSalePayment
    {
        return DB::transaction(function () use ($businessId, $scspId, $data) {
            $payment = SettlementCreditSalePayment::where('business_id', $businessId)->findOrFail($scspId);
            $payment = $this->guardedUpdate($payment, $data);

            $amount = array_key_exists('amount', $data) ? $data['amount'] : $payment->amount;
            $subTotal = array_key_exists('sub_total', $data) ? $data['sub_total'] : $payment->sub_total;
            $totalDiscount = array_key_exists('total_discount', $data) ? $data['total_discount'] : $payment->total_discount;

            if (! empty($payment->pump_payment_id)) {
                PumpOperatorPayment::where('id', $payment->pump_payment_id)
                    ->where('payment_type', 'credit')
                    ->update(['payment_amount' => $amount]);
            }

            if (! empty($payment->daily_voucher_id)) {
                DailyVoucher::where('id', $payment->daily_voucher_id)
                    ->where('business_id', $businessId)
                    ->update(['total_amount' => $subTotal]);
            }

            if (! empty($payment->collection_form_no) && ! empty($payment->pump_operator_id)) {
                $formTotal = SettlementCreditSalePayment::where('business_id', $businessId)
                    ->where('pump_operator_id', $payment->pump_operator_id)
                    ->where('collection_form_no', $payment->collection_form_no)
                    ->sum('sub_total');

                DailyCollection::where('business_id', $businessId)
                    ->where('pump_operator_id', $payment->pump_operator_id)
                    ->where('collection_form_no', $payment->collection_form_no)
                    ->where('type', 'daily_voucher')
                    ->update(['current_amount' => $formTotal]);
            }

            if (! empty($payment->transaction_id)) {
                Transaction::where('id', $payment->transaction_id)->update([
                    'final_total' => $subTotal,
                    'total_before_tax' => $subTotal,
                    'discount_amount' => $totalDiscount,
                ]);
                ContactLedger::where('transaction_id', $payment->transaction_id)->update([
                    'amount' => $subTotal,
                ]);
                AccountTransaction::where('transaction_id', $payment->transaction_id)
                    ->where('type', 'debit')
                    ->update(['amount' => $subTotal]);
            }

            return $payment->refresh();
        });
    }

    public function editCardPayment(int $businessId, int $scpId, array $data): SettlementCardPayment
    {
        return DB::transaction(function () use ($businessId, $scpId, $data) {
            return $this->withinReconcilerContext(function () use ($businessId, $scpId, $data) {
                $payment = SettlementCardPayment::where('business_id', $businessId)->findOrFail($scpId);
                $payment->fill($data);
                $payment->save();

                $this->cascadeAmountToLinkedAccounting(
                    $payment->fresh(),
                    array_key_exists('amount', $data) ? (float) $data['amount'] : (float) $payment->amount
                );

                return $payment->refresh();
            });
        });
    }

    public function editCashPayment(int $businessId, int $sccpId, array $data): SettlementCashPayment
    {
        return DB::transaction(function () use ($businessId, $sccpId, $data) {
            return $this->withinReconcilerContext(function () use ($businessId, $sccpId, $data) {
                $payment = SettlementCashPayment::where('business_id', $businessId)->findOrFail($sccpId);
                $payment->fill($data);
                $payment->save();

                $this->cascadeAmountToLinkedAccounting(
                    $payment->fresh(),
                    array_key_exists('amount', $data) ? (float) $data['amount'] : (float) $payment->amount
                );

                return $payment->refresh();
            });
        });
    }

    public function editChequePayment(int $businessId, int $scqpId, array $data): SettlementChequePayment
    {
        return DB::transaction(function () use ($businessId, $scqpId, $data) {
            return $this->withinReconcilerContext(function () use ($businessId, $scqpId, $data) {
                $payment = SettlementChequePayment::where('business_id', $businessId)->findOrFail($scqpId);
                $payment->fill($data);
                $payment->save();

                $this->cascadeAmountToLinkedAccounting(
                    $payment->fresh(),
                    array_key_exists('amount', $data) ? (float) $data['amount'] : (float) $payment->amount
                );

                return $payment->refresh();
            });
        });
    }

    /**
     * Cascade an amount change on a card/cash/cheque settlement payment row to its
     * linked Transaction, ContactLedger, and AccountTransaction rows.
     *
     * IS1313 (14 May 2026) fix: previously these three editX methods only updated the
     * settlement_*_payments row, leaving the accounting tables stale with the old amount.
     * That produced visible duplicates in the card/cash account book and customer ledger
     * because subsequent finalize-side dedup (ensureSettlementCardAccounting et al.) used
     * fuzzy amount+note matching and missed the stale row, then created a new AT — old +
     * new coexisted in the account book.
     *
     * Cascading the amount now keeps the linked accounting rows in sync so the dedup
     * never sees a mismatch in the first place.
     *
     * Linkage strategy (preferred → fallback):
     *   1. settlement_*_payments.transaction_id (added 2026-05-15 by migration
     *      add_transaction_id_to_settlement_payment_tables). The canonical FK.
     *   2. Transaction.ref_no LIKE 'PD <Type> Payment #<scsp.id>' as a fallback for
     *      historical rows where transaction_id is NULL because they were created
     *      before the migration. Once Phase 2 backfill of the column is complete,
     *      this fallback can be removed.
     */
    private function cascadeAmountToLinkedAccounting($payment, float $amount): void
    {
        if (empty($payment)) {
            return;
        }

        $transactionId = $payment->transaction_id ?? null;

        if (empty($transactionId)) {
            $transactionId = $this->resolveTransactionIdByRefNo($payment);
            // Persist the resolution so future cascades skip the LIKE lookup.
            if (! empty($transactionId)) {
                $payment->transaction_id = $transactionId;
                $payment->save();
            }
        }

        if (empty($transactionId)) {
            return;
        }

        Transaction::where('id', $transactionId)->update([
            'final_total'      => $amount,
            'total_before_tax' => $amount,
        ]);

        ContactLedger::where('transaction_id', $transactionId)->update([
            'amount' => $amount,
        ]);

        AccountTransaction::where('transaction_id', $transactionId)
            ->where('type', 'debit')
            ->update(['amount' => $amount]);
    }

    /**
     * Resolve the linked Transaction.id by matching the ref_no pattern used by
     * SettlementPDController::ensureSettlementCardAccounting and its cash/cheque
     * equivalents: "PD Card Payment #<scsp_id>", "PD Cash Payment #<scsp_id>",
     * "PD Cheque Payment #<scsp_id>".
     */
    private function resolveTransactionIdByRefNo($payment): ?int
    {
        $tableMap = [
            SettlementCardPayment::class   => 'PD Card Payment #',
            SettlementCashPayment::class   => 'PD Cash Payment #',
            SettlementChequePayment::class => 'PD Cheque Payment #',
        ];
        $prefix = $tableMap[get_class($payment)] ?? null;
        if ($prefix === null) {
            return null;
        }

        $row = Transaction::where('business_id', $payment->business_id)
            ->where('ref_no', $prefix . $payment->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        return $row?->id;
    }

    public function deletePaymentLine(int $businessId, string $table, int $rowId): void
    {
        $modelClass = $this->modelClassForTable($table);
        $model = $modelClass::where('business_id', $businessId)->findOrFail($rowId);

        $this->withinReconcilerContext(function () use ($model) {
            $this->deleteLinkedAccounting($model);
            $model->delete();
        });
    }

    private function deleteLinkedAccounting(Model $payment): void
    {
        if (! $payment instanceof SettlementCardPayment
            && ! $payment instanceof SettlementCashPayment
            && ! $payment instanceof SettlementChequePayment) {
            return;
        }

        $transactionId = $payment->transaction_id ?? null;
        if (empty($transactionId)) {
            $transactionId = $this->resolveTransactionIdByRefNo($payment);
        }

        $transactionPaymentId = $payment->customer_payment_id ?? null;

        if (! empty($transactionId)) {
            AccountTransaction::where('transaction_id', $transactionId)->forceDelete();
            ContactLedger::where('transaction_id', $transactionId)->forceDelete();

            TransactionPayment::where('transaction_id', $transactionId)->forceDelete();
            Transaction::where('id', $transactionId)->forceDelete();
        } elseif (! empty($transactionPaymentId)) {
            AccountTransaction::where('transaction_payment_id', $transactionPaymentId)->forceDelete();
            ContactLedger::where('transaction_payment_id', $transactionPaymentId)->forceDelete();
            TransactionPayment::where('id', $transactionPaymentId)->forceDelete();
        }
    }

    private function guardedUpdate(Model $model, array $data): Model
    {
        return $this->withinReconcilerContext(function () use ($model, $data) {
            $model->fill($data);
            $model->save();

            return $model;
        });
    }

    private function withinReconcilerContext(callable $callback)
    {
        app()->instance('petrodirect.reconciler.active', true);
        try {
            return $callback();
        } finally {
            app()->forgetInstance('petrodirect.reconciler.active');
        }
    }

    private function modelClassForTable(string $table): string
    {
        $map = [
            'settlement_card_payments' => SettlementCardPayment::class,
            'settlement_cash_payments' => SettlementCashPayment::class,
            'settlement_cheque_payments' => SettlementChequePayment::class,
            'settlement_credit_sale_payments' => SettlementCreditSalePayment::class,
        ];

        if (! isset($map[$table])) {
            throw new \InvalidArgumentException("Unsupported settlement payment table: {$table}");
        }

        return $map[$table];
    }
}
