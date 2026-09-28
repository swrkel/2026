<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\FinanceKpiSnapshot;
use Modules\Finance\Entities\FinanceTreasuryTransaction;

class FinanceKpiService
{
    public static function generateSnapshot(
        $business_id,
        $location_id = null
    ) {
        $snapshot_date = date('Y-m-d');

        $sales_query = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell');

        $purchase_query = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'purchase');

        if (!empty($location_id)) {
            $sales_query->where('location_id', $location_id);
            $purchase_query->where('location_id', $location_id);
        }

        $total_sales = $sales_query->sum('final_total');
        $total_purchases = $purchase_query->sum('final_total');

        $customer_receipts = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('transactions.location_id', $location_id);
            })
            ->sum('transaction_payments.amount');

        $supplier_payments = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'purchase')
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('transactions.location_id', $location_id);
            })
            ->sum('transaction_payments.amount');

        $receivables = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereIn('payment_status', ['due', 'partial'])
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('location_id', $location_id);
            })
            ->sum('final_total');

        $payables = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'purchase')
            ->whereIn('payment_status', ['due', 'partial'])
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('location_id', $location_id);
            })
            ->sum('final_total');

        $treasury_cash_in = FinanceTreasuryTransaction::where('business_id', $business_id)
            ->whereIn('treasury_type', ['cash_in', 'bank_deposit'])
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('location_id', $location_id);
            })
            ->sum('amount');

        $treasury_cash_out = FinanceTreasuryTransaction::where('business_id', $business_id)
            ->whereIn('treasury_type', ['cash_out', 'bank_withdrawal'])
            ->when($location_id, function ($query) use ($location_id) {
                $query->where('location_id', $location_id);
            })
            ->sum('amount');

        $treasury_net_position =
            $treasury_cash_in - $treasury_cash_out;

        $total_income = $total_sales;
        $total_expenses = $total_purchases;
        $net_profit = $total_income - $total_expenses;

        $gross_margin_percent = null;
        if ($total_sales > 0) {
            $gross_margin_percent =
                (($total_sales - $total_purchases) / $total_sales) * 100;
        }

        $expense_ratio_percent = null;
        if ($total_income > 0) {
            $expense_ratio_percent =
                ($total_expenses / $total_income) * 100;
        }

        $collection_efficiency_percent = null;
        if ($total_sales > 0) {
            $collection_efficiency_percent =
                ($customer_receipts / $total_sales) * 100;
        }

        $liquidity_score = null;
        if ($treasury_cash_out > 0) {
            $liquidity_score =
                ($treasury_cash_in / $treasury_cash_out) * 100;
        }

        return FinanceKpiSnapshot::create([
            'business_id' => $business_id,
            'location_id' => $location_id,
            'snapshot_date' => $snapshot_date,
            'total_income' => $total_income,
            'total_expenses' => $total_expenses,
            'net_profit' => $net_profit,
            'total_sales' => $total_sales,
            'customer_receipts' => $customer_receipts,
            'supplier_payments' => $supplier_payments,
            'receivables' => $receivables,
            'payables' => $payables,
            'treasury_cash_in' => $treasury_cash_in,
            'treasury_cash_out' => $treasury_cash_out,
            'treasury_net_position' => $treasury_net_position,
            'gross_margin_percent' => $gross_margin_percent,
            'expense_ratio_percent' => $expense_ratio_percent,
            'collection_efficiency_percent' => $collection_efficiency_percent,
            'liquidity_score' => $liquidity_score,
            'created_by' => auth()->id(),
        ]);
    }
}