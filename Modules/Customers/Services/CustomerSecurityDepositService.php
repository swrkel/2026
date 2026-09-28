<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Customers\Support\SchemaCache;

/**
 * Read-only Security Deposit history for the dedicated customer page.
 *
 * Security deposits are intentionally separated from Customer Ledger / Total Due.
 * This service understands both the older transaction-based records and the newer
 * standalone Customers payment records without rewriting historical data.
 */
class CustomerSecurityDepositService
{
    public function rows(int $businessId, int $customerId): Collection
    {
        $rows = collect();
        $seenTransactionIds = [];
        $seenPaymentIds = [];

        if (SchemaCache::hasTable('transactions')) {
            $transactionQuery = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->where('type', 'security_deposit');

            if (SchemaCache::hasColumn('transactions', 'deleted_at')) {
                $transactionQuery->whereNull('deleted_at');
            }

            $transactions = $transactionQuery
                ->orderBy(SchemaCache::hasColumn('transactions', 'transaction_date') ? 'transaction_date' : 'id')
                ->orderBy('id')
                ->get();

            $transactionIds = $transactions->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
            $paymentsByTransaction = collect();

            if (!empty($transactionIds) && SchemaCache::hasTable('transaction_payments')) {
                $paymentQuery = DB::table('transaction_payments')
                    ->whereIn('transaction_id', $transactionIds);

                if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                    $paymentQuery->where('business_id', $businessId);
                }
                if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                    $paymentQuery->whereNull('deleted_at');
                }

                $paymentsByTransaction = $paymentQuery
                    ->orderBy(SchemaCache::hasColumn('transaction_payments', 'paid_on') ? 'paid_on' : 'id')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('transaction_id');
            }

            foreach ($transactions as $transaction) {
                $transactionId = (int) ($transaction->id ?? 0);
                $seenTransactionIds[$transactionId] = true;
                $payments = $paymentsByTransaction->get($transactionId, collect());

                if ($payments->isEmpty()) {
                    $rows->push($this->makeRowFromTransaction($transaction));
                    continue;
                }

                foreach ($payments as $payment) {
                    $paymentId = (int) ($payment->id ?? 0);
                    if ($paymentId > 0) {
                        $seenPaymentIds[$paymentId] = true;
                    }
                    $rows->push($this->makeRowFromTransactionPayment($transaction, $payment));
                }
            }
        }

        // Current standalone Customers Security Deposit entries do not need a
        // transactions row. Their durable payment reference identifies them.
        if (SchemaCache::hasTable('transaction_payments')) {
            $directQuery = DB::table('transaction_payments');

            if (SchemaCache::hasColumn('transaction_payments', 'business_id')) {
                $directQuery->where('business_id', $businessId);
            }
            if (SchemaCache::hasColumn('transaction_payments', 'payment_for')) {
                $directQuery->where('payment_for', $customerId);
            } else {
                $directQuery->whereRaw('1 = 0');
            }
            if (SchemaCache::hasColumn('transaction_payments', 'deleted_at')) {
                $directQuery->whereNull('deleted_at');
            }

            $directQuery->where(function ($scope) {
                $hasReference = SchemaCache::hasColumn('transaction_payments', 'payment_ref_no');
                $hasPaidInType = SchemaCache::hasColumn('transaction_payments', 'paid_in_type');

                if ($hasReference) {
                    $scope->where('payment_ref_no', 'like', 'CUS-SECURITY-DEPOSIT-%');
                }
                if ($hasPaidInType) {
                    $method = $hasReference ? 'orWhereIn' : 'whereIn';
                    $scope->{$method}('paid_in_type', ['security_deposit', 'security deposit']);
                }
                if (!$hasReference && !$hasPaidInType) {
                    $scope->whereRaw('1 = 0');
                }
            });

            foreach ($directQuery->orderBy('id')->get() as $payment) {
                $paymentId = (int) ($payment->id ?? 0);
                if ($paymentId > 0 && isset($seenPaymentIds[$paymentId])) {
                    continue;
                }

                $transactionId = (int) ($payment->transaction_id ?? 0);
                if ($transactionId > 0 && isset($seenTransactionIds[$transactionId])) {
                    continue;
                }

                if ($paymentId > 0) {
                    $seenPaymentIds[$paymentId] = true;
                }
                $rows->push($this->makeRowFromDirectPayment($payment));
            }
        }

        // Compatibility for very old deposits that exist only as a ledger row.
        if (SchemaCache::hasTable('contact_ledgers')) {
            $ledgerQuery = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId);

            if (SchemaCache::hasColumn('contact_ledgers', 'deleted_at')) {
                $ledgerQuery->whereNull('deleted_at');
            }

            $hasDescription = SchemaCache::hasColumn('contact_ledgers', 'description');
            $hasNote = SchemaCache::hasColumn('contact_ledgers', 'note');
            if ($hasDescription || $hasNote) {
                $ledgerQuery->where(function ($scope) use ($hasDescription, $hasNote) {
                    if ($hasDescription) {
                        $scope->whereRaw("LOWER(COALESCE(description, '')) LIKE '%security deposit%'");
                    }
                    if ($hasNote) {
                        $method = $hasDescription ? 'orWhereRaw' : 'whereRaw';
                        $scope->{$method}("LOWER(COALESCE(note, '')) LIKE '%security deposit%'");
                    }
                });

                foreach ($ledgerQuery->orderBy('id')->get() as $ledger) {
                    $transactionId = (int) ($ledger->transaction_id ?? 0);
                    $paymentId = (int) ($ledger->transaction_payment_id ?? 0);
                    if (($transactionId > 0 && isset($seenTransactionIds[$transactionId]))
                        || ($paymentId > 0 && isset($seenPaymentIds[$paymentId]))) {
                        continue;
                    }

                    if ($transactionId > 0) {
                        $seenTransactionIds[$transactionId] = true;
                    }
                    if ($paymentId > 0) {
                        $seenPaymentIds[$paymentId] = true;
                    }
                    $rows->push($this->makeRowFromLedger($ledger));
                }
            }
        }

        return $rows
            ->sortBy(function ($row) {
                return sprintf(
                    '%s|%020d|%020d',
                    (string) ($row->deposit_date ?? ''),
                    (int) ($row->transaction_id ?? 0),
                    (int) ($row->payment_id ?? 0)
                );
            })
            ->values();
    }

    private function makeRowFromTransaction($transaction): object
    {
        return (object) [
            'deposit_date' => $transaction->transaction_date ?? $transaction->created_at ?? null,
            'created_at' => $transaction->created_at ?? null,
            'amount' => abs((float) ($transaction->final_total ?? 0)),
            'method' => '',
            'payment_ref_no' => $transaction->invoice_no ?? $transaction->ref_no ?? '',
            'cheque_number' => '',
            'cheque_date' => null,
            'bank_name' => '',
            'status' => $transaction->payment_status ?? $transaction->status ?? '',
            'note' => $transaction->additional_notes ?? $transaction->staff_note ?? '',
            'transaction_id' => (int) ($transaction->id ?? 0),
            'payment_id' => null,
            'source' => 'Legacy Transaction',
        ];
    }

    private function makeRowFromTransactionPayment($transaction, $payment): object
    {
        return (object) [
            'deposit_date' => $payment->paid_on ?? $transaction->transaction_date ?? $payment->created_at ?? null,
            'created_at' => $payment->created_at ?? $transaction->created_at ?? null,
            'amount' => abs((float) ($payment->amount ?? $transaction->final_total ?? 0)),
            'method' => $payment->method ?? '',
            'payment_ref_no' => $payment->payment_ref_no ?? $transaction->invoice_no ?? $transaction->ref_no ?? '',
            'cheque_number' => $payment->cheque_number ?? '',
            'cheque_date' => $payment->cheque_date ?? null,
            'bank_name' => $payment->bank_name ?? '',
            'status' => $transaction->payment_status ?? 'paid',
            'note' => $payment->note ?? $transaction->additional_notes ?? '',
            'transaction_id' => (int) ($transaction->id ?? 0),
            'payment_id' => (int) ($payment->id ?? 0),
            'source' => 'Security Deposit',
        ];
    }

    private function makeRowFromDirectPayment($payment): object
    {
        return (object) [
            'deposit_date' => $payment->paid_on ?? $payment->created_at ?? null,
            'created_at' => $payment->created_at ?? null,
            'amount' => abs((float) ($payment->amount ?? 0)),
            'method' => $payment->method ?? '',
            'payment_ref_no' => $payment->payment_ref_no ?? '',
            'cheque_number' => $payment->cheque_number ?? '',
            'cheque_date' => $payment->cheque_date ?? null,
            'bank_name' => $payment->bank_name ?? '',
            'status' => 'paid',
            'note' => $payment->note ?? '',
            'transaction_id' => (int) ($payment->transaction_id ?? 0) ?: null,
            'payment_id' => (int) ($payment->id ?? 0),
            'source' => 'Security Deposit',
        ];
    }

    private function makeRowFromLedger($ledger): object
    {
        return (object) [
            'deposit_date' => $ledger->operation_date ?? $ledger->created_at ?? null,
            'created_at' => $ledger->created_at ?? null,
            'amount' => abs((float) ($ledger->amount ?? 0)),
            'method' => '',
            'payment_ref_no' => '',
            'cheque_number' => '',
            'cheque_date' => null,
            'bank_name' => '',
            'status' => 'posted',
            'note' => $ledger->description ?? $ledger->note ?? '',
            'transaction_id' => (int) ($ledger->transaction_id ?? 0) ?: null,
            'payment_id' => (int) ($ledger->transaction_payment_id ?? 0) ?: null,
            'source' => 'Legacy Ledger',
        ];
    }
}
