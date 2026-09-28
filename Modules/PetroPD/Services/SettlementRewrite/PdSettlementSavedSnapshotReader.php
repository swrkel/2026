<?php

namespace Modules\PetroPD\Services\SettlementRewrite;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single read layer for already-saved PD settlements.
 *
 * IMPORTANT RULE:
 * List / View / Print / Reports must read from the same settlement-marked rows.
 * They must not recalculate independently from live pumper dashboard tables.
 *
 * Confirmed rewrite source-of-truth after save:
 * - pumper_day_entries, linked by settlement_no, for meter sales
 * - pump_operator_payments, linked by settlement_no/is_used, for all payment rows
 * - pump_operator_other_sales, linked by settlement_no or settlement_id, for other sales
 */
class PdSettlementSavedSnapshotReader
{
    public function snapshot(int $businessId, int $settlementId, ?string $settlementNo = null): array
    {
        $settlement = DB::table('settlements')
            ->where('business_id', $businessId)
            ->where('id', $settlementId)
            ->first();

        $settlementNo = $settlementNo ?: (string) ($settlement->settlement_no ?? $settlementId);

        return [
            'settlement' => $settlement,
            'meter_sales' => $this->meterSales($businessId, $settlementId, $settlementNo),
            'other_sales' => $this->otherSales($businessId, $settlementId, $settlementNo),
            'payments' => $this->payments($businessId, $settlementId, $settlementNo),
        ];
    }

    public function meterSales(int $businessId, int $settlementId, string $settlementNo): Collection
    {
        if (! $this->tableExists('pumper_day_entries')) {
            return collect();
        }

        $query = DB::table('pumper_day_entries')
            ->where('business_id', $businessId);

        if ($settlementNo !== '' && Schema::hasColumn('pumper_day_entries', 'settlement_no')) {
            $query->where('settlement_no', $settlementNo);
        } elseif ($settlementId > 0 && Schema::hasColumn('pumper_day_entries', 'settlement_id')) {
            $query->where('settlement_id', $settlementId);
        } else {
            return collect();
        }

        return $this->dedupeMeterRows($query->get());
    }

    public function otherSales(int $businessId, int $settlementId, string $settlementNo): Collection
    {
        if (! $this->tableExists('pump_operator_other_sales')) {
            return collect();
        }

        $query = DB::table('pump_operator_other_sales')
            ->where('business_id', $businessId);

        if ($settlementNo !== '' && Schema::hasColumn('pump_operator_other_sales', 'settlement_no')) {
            $query->where('settlement_no', $settlementNo);
        } elseif ($settlementId > 0 && Schema::hasColumn('pump_operator_other_sales', 'settlement_id')) {
            $query->where('settlement_id', $settlementId);
        } else {
            return collect();
        }

        return $query->get();
    }

    public function payments(int $businessId, int $settlementId, string $settlementNo): Collection
    {
        $rows = $this->pumpOperatorPayments($businessId, $settlementNo);

        // Legacy fallback only for old saved settlements that do not yet have
        // pump_operator_payments linked by settlement_no.
        if ($rows->isEmpty()) {
            $rows = $this->legacySettlementPayments($businessId, $settlementId, $settlementNo);
        }

        return $rows->map(function ($row) {
            $row->payment_type = PdSettlementPaymentTypeNormalizer::normalize($row->payment_type ?? $row->type ?? null);
            $amount = PdSettlementPaymentTypeNormalizer::amount($row);
            $row->payment_amount = $amount;
            $row->amount = $amount;
            return $row;
        })->values();
    }

    protected function pumpOperatorPayments(int $businessId, string $settlementNo): Collection
    {
        if (! $this->tableExists('pump_operator_payments') || $settlementNo === '') {
            return collect();
        }

        return DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where('settlement_no', $settlementNo)
            ->get();
    }

    protected function legacySettlementPayments(int $businessId, int $settlementId, string $settlementNo): Collection
    {
        $tables = [
            'settlement_cash_payments' => 'cash',
            'settlement_card_payments' => 'card',
            'settlement_cheque_payments' => 'cheque',
            'settlement_credit_sale_payments' => 'credit_sale',
            'settlement_expense_payments' => 'expense',
            'settlement_excess_payments' => 'excess',
            'settlement_shortage_payments' => 'shortage',
            'settlement_loan_payments' => 'loan_payment',
            'settlement_customer_loans' => 'loan_to_customer',
            'settlement_drawing_payments' => 'owners_drawing',
        ];

        $rows = collect();
        foreach ($tables as $table => $type) {
            if (! $this->tableExists($table)) {
                continue;
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

            $rows = $rows->merge($query->get()->map(function ($row) use ($type) {
                $row->payment_type = $type;
                return $row;
            }));
        }

        return $rows;
    }

    public function paymentDetailsTotal(Collection $payments): float
    {
        $totals = app(PdSettlementTotalsService::class)->paymentTotals($payments);
        return app(PdSettlementTotalsService::class)->paymentDetailsTotal($totals);
    }

    private function dedupeMeterRows(Collection $rows): Collection
    {
        return $rows->unique(function ($row) {
            return implode('|', [
                $row->pumper_assignment_id ?? '',
                $row->pump_id ?? '',
                $row->starting_meter ?? '',
                $row->closing_meter ?? '',
                $row->sold_ltr ?? $row->sold_qty ?? '',
                $row->amount ?? $row->sub_total ?? '',
            ]);
        })->values();
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
