<?php

namespace Modules\PetroGeneral\Http\Controllers\Traits;

use App\AccountTransaction;
use App\ContactLedger;
use App\Transaction;
use App\TransactionPayment;
use Modules\PetroGeneral\Entities\DailyCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait UpdatesSettlementTransactions
{
    /**
     * Update all related transactions, account_transactions, and contact_ledgers when settlement date changes.
     * Ensures transactions appear in correct chronological order in account books and ledgers,
     * and balances are auto-adjusted globally.
     *
     * @param \Modules\PetroGeneral\Entities\Settlement $settlement
     * @param string $newTransactionDate
     * @return void
     */
    private function updateSettlementRelatedTransactions($settlement, $newTransactionDate)
    {
        DB::beginTransaction();
        try {
            $settlement_no = $settlement->settlement_no;
            $business_id = $settlement->business_id;
            $newDate = \Carbon::parse($newTransactionDate)->format('Y-m-d H:i:s');

            $transactionIds = collect();

            if (config('petrogeneral.fk_only_settlement_lookup', false)) {
                $transactionIds = Transaction::where('business_id', $business_id)
                    ->where('petro_settlement_id', $settlement->id)
                    ->whereNull('deleted_at')
                    ->pluck('id');
            } else {

            // Step 4 — primary lookup via the new FK. New writes (post-migration) carry
            // petro_settlement_id, so this query is fast and unambiguous.
            if (Schema::hasColumn('transactions', 'petro_settlement_id')) {
                $fkTransactions = Transaction::where('business_id', $business_id)
                    ->where('petro_settlement_id', $settlement->id)
                    ->whereNull('deleted_at')
                    ->pluck('id');
                $transactionIds = $transactionIds->merge($fkTransactions);
            }

            // DAY1-FALLBACK: LIKE-based lookup for historical rows that have no FK set.
            // Restricted to rows where petro_settlement_id IS NULL so the index path
            // does not double-count newer writes. Remove this whole block after week 1
            // backfill populates petro_settlement_id across historical rows.
            $likeBaseQuery = Transaction::where('business_id', $business_id)
                ->whereNull('deleted_at');
            if (Schema::hasColumn('transactions', 'petro_settlement_id')) {
                $likeBaseQuery->whereNull('petro_settlement_id');
            }

            $directTransactions = (clone $likeBaseQuery)
                ->where('invoice_no', $settlement_no)
                ->pluck('id');
            $transactionIds = $transactionIds->merge($directTransactions);

            $refNoTransactions = (clone $likeBaseQuery)
                ->where(function($query) use ($settlement_no) {
                    $query->where('ref_no', 'like', '%settlement #' . $settlement_no . '%')
                          ->orWhere('ref_no', 'like', '%Settlement No: ' . $settlement_no . '%')
                          ->orWhere('ref_no', 'like', '%Settlement No.%' . $settlement_no . '%')
                          ->orWhere('ref_no', 'like', '%' . $settlement_no . '%');
                })
                ->pluck('id');
            $transactionIds = $transactionIds->merge($refNoTransactions);
            }

            $dailyCollections = DailyCollection::where('settlement_id', $settlement->id)
                ->where('business_id', $business_id)
                ->pluck('collection_form_no');
            
            if ($dailyCollections->isNotEmpty()) {
                $collectionTransactions = Transaction::where('business_id', $business_id)
                    ->where(function($query) use ($dailyCollections) {
                        foreach ($dailyCollections as $formNo) {
                            $query->orWhere('ref_no', 'like', '%Daily Collection #' . $formNo . '%');
                        }
                    })
                    ->whereNull('deleted_at')
                    ->pluck('id');
                $transactionIds = $transactionIds->merge($collectionTransactions);
            }

            $transactionIds = $transactionIds->unique()->values();

            $paymentIds = collect();
            if ($transactionIds->isNotEmpty()) {
                $paymentIds = TransactionPayment::whereIn('transaction_id', $transactionIds)
                    ->pluck('id');
            }

            if ($transactionIds->isNotEmpty()) {
                Transaction::whereIn('id', $transactionIds)
                    ->update(['transaction_date' => $newDate]);
            }

            if ($paymentIds->isNotEmpty()) {
                TransactionPayment::whereIn('id', $paymentIds)
                    ->update(['paid_on' => $newDate]);
            }

            if ($transactionIds->isNotEmpty()) {
                AccountTransaction::whereIn('transaction_id', $transactionIds)
                    ->whereNull('deleted_at')
                    ->update(['operation_date' => $newDate]);
            }

            if ($paymentIds->isNotEmpty()) {
                AccountTransaction::whereIn('transaction_payment_id', $paymentIds)
                    ->whereNull('deleted_at')
                    ->update(['operation_date' => $newDate]);
            }

            AccountTransaction::where('business_id', $business_id)
                ->where(function($query) use ($settlement_no) {
                    $query->where('note', 'like', '%Settlement No: ' . $settlement_no . '%')
                          ->orWhere('note', 'like', '%Settlement No.%' . $settlement_no . '%')
                          ->orWhere('note', 'like', '%settlement #' . $settlement_no . '%')
                          ->orWhere('note', 'like', '%' . $settlement_no . '%');
                })
                ->whereNull('deleted_at')
                ->update(['operation_date' => $newDate]);

            $shiftNumbers = [];
            if (!empty($settlement->work_shift)) {
                $workShifts = is_string($settlement->work_shift) ? json_decode($settlement->work_shift, true) : $settlement->work_shift;
                if (is_array($workShifts)) {
                    $shiftNumbers = $workShifts;
                } elseif (!empty($workShifts)) {
                    $shiftNumbers = [$workShifts];
                }
            }
            
            if (!empty($shiftNumbers)) {
                AccountTransaction::where('business_id', $business_id)
                    ->whereIn('shift_number', $shiftNumbers)
                    ->where(function($query) use ($settlement_no) {
                        $query->where('note', 'like', '%' . $settlement_no . '%')
                              ->orWhere('note', 'like', '%Settlement No: ' . $settlement_no . '%')
                              ->orWhere('note', 'like', '%Settlement No.%' . $settlement_no . '%');
                    })
                    ->whereNull('deleted_at')
                    ->update(['operation_date' => $newDate]);
            }

            if ($transactionIds->isNotEmpty()) {
                ContactLedger::whereIn('transaction_id', $transactionIds)
                    ->whereNull('deleted_at')
                    ->update(['operation_date' => $newDate]);
            }

            if ($paymentIds->isNotEmpty()) {
                ContactLedger::whereIn('transaction_payment_id', $paymentIds)
                    ->whereNull('deleted_at')
                    ->update(['operation_date' => $newDate]);
            }

            ContactLedger::where(function($query) use ($settlement_no) {
                    $query->where('note', 'like', '%Settlement No: ' . $settlement_no . '%')
                          ->orWhere('note', 'like', '%Settlement No.%' . $settlement_no . '%')
                          ->orWhere('note', 'like', '%settlement #' . $settlement_no . '%')
                          ->orWhere('note', 'like', '%' . $settlement_no . '%');
                })
                ->whereNull('deleted_at')
                ->update(['operation_date' => $newDate]);

            DB::commit();

            $accountTransactionsCount = 0;
            if ($transactionIds->isNotEmpty()) {
                $accountTransactionsCount += AccountTransaction::whereIn('transaction_id', $transactionIds)->count();
            }
            if ($paymentIds->isNotEmpty()) {
                $accountTransactionsCount += AccountTransaction::whereIn('transaction_payment_id', $paymentIds)->count();
            }
            $accountTransactionsCount += AccountTransaction::where('business_id', $business_id)
                ->where(function($query) use ($settlement_no) {
                    $query->where('note', 'like', '%' . $settlement_no . '%');
                })
                ->whereNull('deleted_at')
                ->count();

            $contactLedgersCount = 0;
            if ($transactionIds->isNotEmpty()) {
                $contactLedgersCount += ContactLedger::whereIn('transaction_id', $transactionIds)->count();
            }
            if ($paymentIds->isNotEmpty()) {
                $contactLedgersCount += ContactLedger::whereIn('transaction_payment_id', $paymentIds)->count();
            }

            Log::info('Updated settlement related transactions', [
                'settlement_no' => $settlement_no,
                'settlement_id' => $settlement->id,
                'new_transaction_date' => $newDate,
                'transactions_updated' => $transactionIds->count(),
                'payments_updated' => $paymentIds->count(),
                'account_transactions_updated' => $accountTransactionsCount,
                'contact_ledgers_updated' => $contactLedgersCount,
                'shift_numbers' => $shiftNumbers
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating settlement related transactions', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

