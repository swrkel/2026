<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissingWalkInDebitLedgers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $walk_in_customers = \App\Contact::where('type', 'customer')
            ->where('is_default', 1)
            ->get();

        foreach ($walk_in_customers as $walk_in) {
            $credit_ledgers = \App\ContactLedger::where('contact_id', $walk_in->id)
                ->where('type', 'credit')
                ->where(function ($query) {
                    $query->whereIn('sub_type', ['payment', 'cash_payment', 'card_payment', 'cheque_payment'])
                        ->orWhereIn('transaction_id', \App\Transaction::whereIn('sub_type', ['cash_payment', 'card_payment', 'cheque_payment'])->select('id'));
                })
                ->whereNotNull('transaction_payment_id')
                ->get();

            foreach ($credit_ledgers as $credit_ledger) {
                // Check if corresponding debit ledger exists
                $debit_exists = \App\ContactLedger::where('transaction_id', $credit_ledger->transaction_id)
                    ->where('contact_id', $walk_in->id)
                    ->where('type', 'debit')
                    ->where('transaction_payment_id', $credit_ledger->transaction_payment_id)
                    ->exists();

                if (!$debit_exists) {
                    $debit_data = [
                        'contact_id' => $credit_ledger->contact_id,
                        'amount' => $credit_ledger->amount,
                        'type' => 'debit',
                        'sub_type' => 'sell',
                        'operation_date' => $credit_ledger->operation_date,
                        'created_by' => $credit_ledger->created_by,
                        'transaction_id' => $credit_ledger->transaction_id,
                        'transaction_payment_id' => $credit_ledger->transaction_payment_id,
                        'note' => $credit_ledger->note,
                        'transaction_sell_line_id' => $credit_ledger->transaction_sell_line_id,
                        'income_type' => $credit_ledger->income_type,
                        'installment_id' => $credit_ledger->installment_id,
                    ];

                    if (\Illuminate\Support\Facades\Schema::hasColumn('contact_ledgers', 'business_id')) {
                        $debit_data['business_id'] = $credit_ledger->business_id;
                    }

                    \App\ContactLedger::create($debit_data);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reversing this is optional and not recommended since it would delete corrected ledger data.
    }
}
