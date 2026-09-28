<?php

namespace Modules\PetroPD\Services;

use App\AccountTransaction;
use App\ContactLedger;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;

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
                $this->syncMasterAmount(
                    (int) $payment->pump_payment_id,
                    'credit',
                    (float) $amount,
                    (float) $totalDiscount,
                    (float) $subTotal
                );
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

                $authoritativeAmount = array_key_exists('amount', $data)
                    ? (float) $data['amount']
                    : (float) $payment->amount;

                if (! empty($payment->pump_payment_id)) {
                    $this->syncMasterAmount(
                        (int) $payment->pump_payment_id,
                        'card',
                        $authoritativeAmount,
                        0.0,
                        $authoritativeAmount
                    );
                }

                $this->cascadeAmountToLinkedAccounting(
                    $payment->fresh(),
                    $authoritativeAmount
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

                $authoritativeAmount = array_key_exists('amount', $data)
                    ? (float) $data['amount']
                    : (float) $payment->amount;

                if (! empty($payment->pump_payment_id)) {
                    $this->syncMasterAmount(
                        (int) $payment->pump_payment_id,
                        'cash',
                        $authoritativeAmount,
                        0.0,
                        $authoritativeAmount
                    );
                }

                $this->cascadeAmountToLinkedAccounting(
                    $payment->fresh(),
                    $authoritativeAmount
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

                $authoritativeAmount = array_key_exists('amount', $data)
                    ? (float) $data['amount']
                    : (float) $payment->amount;

                if (! empty($payment->pump_payment_id)) {
                    $this->syncMasterAmount(
                        (int) $payment->pump_payment_id,
                        'cheque',
                        $authoritativeAmount,
                        0.0,
                        $authoritativeAmount
                    );
                }

                $this->cascadeAmountToLinkedAccounting(
                    $payment->fresh(),
                    $authoritativeAmount
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
    /**
     * Keep pump_operator_payments as the single authoritative financial row.
     * The detail row and all accounting cascades are updated in the same DB
     * transaction by the caller.
     */
    private function syncMasterAmount(
        int $pumpPaymentId,
        string $paymentType,
        float $grossAmount,
        float $discountAmount,
        float $netAmount
    ): void {
        $master = PumpOperatorPayment::where('id', $pumpPaymentId)
            ->lockForUpdate()
            ->firstOrFail();

        $normalizedType = strtolower((string) $master->payment_type);
        $allowedAliases = match ($paymentType) {
            'card' => ['card', 'cards'],
            'cheque' => ['cheque', 'cheques'],
            'credit' => ['credit', 'multiple_credit'],
            default => [$paymentType],
        };

        if (! in_array($normalizedType, $allowedAliases, true)) {
            throw new \RuntimeException('Settlement payment type does not match its authoritative Pump Operator Payment.');
        }

        $payload = ['payment_amount' => round($grossAmount, 4)];
        if (Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
            $payload['gross_amount'] = round($grossAmount, 4);
        }
        if (Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
            $payload['discount_amount'] = round($discountAmount, 4);
        }
        if (Schema::hasColumn('pump_operator_payments', 'net_amount')) {
            $payload['net_amount'] = round($netAmount, 4);
        }

        // The model/DB guards keep business, operator, Shift ID and source
        // identity immutable while permitting an authorised amount correction.
        $master->fill($payload);
        $master->save();
    }

    private function cascadeAmountToLinkedAccounting($payment, float $amount): void
    {
        if (empty($payment)) {
            return;
        }

        $transactionId = $payment->transaction_id ?? null;

        if (empty($transactionId)) {
            $transactionId = $this->resolveTransactionIdByRefNo($payment);
        }

        if (empty($transactionId)) {
            $transactionId = $this->resolveTransactionIdByAccountNote($payment);
        }

        // Persist the resolution so future cascades skip the lookups above.
        if (! empty($transactionId) && empty($payment->transaction_id)) {
            $payment->transaction_id = $transactionId;
            $payment->save();
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

    /**
     * LA-1160 (10 Aug 2026): resolve the linked Transaction.id for a settlement CASH
     * payment through the idempotency token that the finalize code stamps into the
     * AccountTransaction note.
     *
     * Reported symptom: editing a cash payment from PD Operators > Payment Summary
     * left BOTH the original and the edited amount showing in Finance > List
     * Accounts > Cash Account. The edit itself was correct - settlement_cash_payments,
     * pump_operator_payments and daily_collections all took the new amount - but the
     * accounting rows kept the old one, so the account book showed two entries and
     * the running balance was inflated by the difference.
     *
     * The cause is that neither existing lookup can ever succeed for cash:
     *   - Nothing in this module writes settlement_cash_payments.transaction_id, so
     *     the canonical FK is always NULL.
     *   - resolveTransactionIdByRefNo() looks for ref_no 'PD Cash Payment #<id>',
     *     but CreatesPdSettlements builds the cash Transaction through
     *     createTransaction() without passing $ref_no, so ref_no is NULL on all of
     *     them.
     * cascadeAmountToLinkedAccounting() therefore returned early on every cash edit
     * and the IS1313 sync it was written to perform never actually ran for cash.
     *
     * The token is the one built in CreatesPdSettlements when the account entry is
     * written:
     *     [SETL:<settlement_no>|BIZ:<business_id>|ACCT:<account_id>|CP:<cash_payment_id>]
     * Matching on '|CP:<id>]' anchors both ends of the id, so CP:1 cannot match
     * CP:12 the way the looser '%CP:<id>%' fallback in the finalize dedup can.
     *
     * Deliberately cash-only. Card and cheque account entries are not written with
     * this token, and guessing at their note format risks repointing the wrong
     * accounting row.
     */
    private function resolveTransactionIdByAccountNote($payment): ?int
    {
        if (! $payment instanceof SettlementCashPayment) {
            return null;
        }

        if (empty($payment->id) || empty($payment->business_id)) {
            return null;
        }

        $row = AccountTransaction::where('business_id', $payment->business_id)
            ->where('type', 'debit')
            ->where(function ($query) {
                $query->where('sub_type', 'cash_payment')
                    ->orWhere('sub_type', 'settlement_cash_payment');
            })
            ->where('note', 'LIKE', '%|CP:' . $payment->id . ']%')
            ->whereNotNull('transaction_id')
            ->orderByDesc('id')
            ->first();

        return $row?->transaction_id;
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
        app()->instance('petropd.reconciler.active', true);
        try {
            return $callback();
        } finally {
            app()->forgetInstance('petropd.reconciler.active');
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
