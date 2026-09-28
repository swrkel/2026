<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Reconciliation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Services\SettlementRewrite\PdSettlementPaymentTypeNormalizer;

/**
 * Reads totals only from the saved settlement-marked rows.
 *
 * Primary source after the rewrite:
 * - pump_operator_payments by settlement_no for payment details total
 * - pumper_day_entries by settlement_no for meter sales total
 * - pump_operator_other_sales by settlement_no/settlement_id for other sales total
 *
 * Legacy settlement_* payment tables are fallback-only for old already-saved data.
 */
class SettlementSavedTotalsReader
{
    public function totals($settlement): array
    {
        $businessId = (int) ($settlement->business_id ?? 0);
        $settlementId = (int) ($settlement->id ?? 0);
        $settlementNo = (string) ($settlement->settlement_no ?? '');

        $paymentTotals = $this->paymentTotals($businessId, $settlementId, $settlementNo);

        $paymentDetailsTotal = $paymentTotals['cash']
            + $paymentTotals['card']
            + $paymentTotals['cheque']
            + $paymentTotals['credit_sale']
            + $paymentTotals['loan_payment']
            + $paymentTotals['excess']
            - $paymentTotals['shortage'];

        return [
            'settlement_id' => $settlementId,
            'settlement_no' => $settlementNo,
            'payment_totals' => $paymentTotals,
            'payment_details_total' => round($paymentDetailsTotal, 4),
            'meter_sales_total' => $this->sumMeterSales($businessId, $settlementNo, $settlementId),
            'other_sales_total' => $this->sumOtherSales($businessId, $settlementNo, $settlementId),
        ];
    }

    protected function paymentTotals(int $businessId, int $settlementId, string $settlementNo): array
    {
        $totals = [
            'cash' => 0.0,
            'card' => 0.0,
            'cheque' => 0.0,
            'credit_sale' => 0.0,
            'loan_payment' => 0.0,
            'loan_to_customer' => 0.0,
            'expense' => 0.0,
            'shortage' => 0.0,
            'excess' => 0.0,
            'drawing' => 0.0,
            'owners_drawing' => 0.0,
        ];

        $rows = $this->pumpOperatorPaymentRows($businessId, $settlementNo);
        if (! empty($rows)) {
            foreach ($rows as $row) {
                $type = PdSettlementPaymentTypeNormalizer::normalize($row->payment_type ?? $row->type ?? '');
                if (! array_key_exists($type, $totals)) {
                    continue;
                }
                $totals[$type] += PdSettlementPaymentTypeNormalizer::amount($row);
            }

            return array_map(fn ($value) => round((float) $value, 4), $totals);
        }

        // Fallback only for old saved settlements that were created before the rewrite.
        $legacyTables = [
            'cash' => 'settlement_cash_payments',
            'card' => 'settlement_card_payments',
            'cheque' => 'settlement_cheque_payments',
            'credit_sale' => 'settlement_credit_sale_payments',
            'loan_payment' => 'settlement_loan_payments',
            'expense' => 'settlement_expense_payments',
            'shortage' => 'settlement_shortage_payments',
            'excess' => 'settlement_excess_payments',
            'drawing' => 'settlement_drawing_payments',
        ];

        foreach ($legacyTables as $type => $table) {
            $totals[$type] = $this->sumPaymentTable($table, $businessId, $settlementId, $settlementNo);
        }

        return array_map(fn ($value) => round((float) $value, 4), $totals);
    }

    protected function pumpOperatorPaymentRows(int $businessId, string $settlementNo): array
    {
        if (! $this->tableExists('pump_operator_payments') || $businessId <= 0 || $settlementNo === '') {
            return [];
        }

        return DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where('settlement_no', $settlementNo)
            ->get()
            ->all();
    }

    protected function sumPaymentTable(string $table, int $businessId, int $settlementId, string $settlementNo): float
    {
        if (! $this->tableExists($table) || $businessId <= 0 || ($settlementId <= 0 && $settlementNo === '')) {
            return 0.0;
        }

        $query = DB::table($table)->where('business_id', $businessId);

        if (Schema::hasColumn($table, 'settlement_no')) {
            $query->where(function ($q) use ($settlementId, $settlementNo) {
                if ($settlementId > 0) {
                    $q->orWhere('settlement_no', $settlementId)->orWhere('settlement_no', (string) $settlementId);
                }
                if ($settlementNo !== '') {
                    $q->orWhere('settlement_no', $settlementNo);
                }
            });
        }

        $amountColumn = Schema::hasColumn($table, 'amount') ? 'amount' : (Schema::hasColumn($table, 'payment_amount') ? 'payment_amount' : null);
        return $amountColumn ? (float) $query->sum($amountColumn) : 0.0;
    }

    protected function sumMeterSales(int $businessId, string $settlementNo, int $settlementId = 0): float
    {
        if (! $this->tableExists('pumper_day_entries')) {
            return 0.0;
        }

        $query = DB::table('pumper_day_entries')->where('business_id', $businessId);
        if ($settlementNo !== '' && Schema::hasColumn('pumper_day_entries', 'settlement_no')) {
            $query->where('settlement_no', $settlementNo);
        } elseif ($settlementId > 0 && Schema::hasColumn('pumper_day_entries', 'settlement_id')) {
            $query->where('settlement_id', $settlementId);
        } else {
            return 0.0;
        }

        return (float) $query->selectRaw('SUM(COALESCE(amount, 0)) as total')->value('total');
    }

    protected function sumOtherSales(int $businessId, string $settlementNo, int $settlementId = 0): float
    {
        if (! $this->tableExists('pump_operator_other_sales')) {
            return 0.0;
        }

        $query = DB::table('pump_operator_other_sales')->where('business_id', $businessId);
        if ($settlementNo !== '' && Schema::hasColumn('pump_operator_other_sales', 'settlement_no')) {
            $query->where('settlement_no', $settlementNo);
        } elseif ($settlementId > 0 && Schema::hasColumn('pump_operator_other_sales', 'settlement_id')) {
            $query->where('settlement_id', $settlementId);
        } else {
            return 0.0;
        }

        return (float) $query->selectRaw('SUM(COALESCE(sub_total, 0)) as total')->value('total');
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
