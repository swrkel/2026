<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PetroPD\Services\SettlementRewrite\Reconciliation\SettlementSavedTotalsReader;
use Modules\PetroPD\Services\SettlementRewrite\Reconciliation\SettlementConsistencyChecker;

/**
 * Builds a normalized posting context from the saved settlement record and calls
 * the new posting bridge once. This is deliberately defensive: posting failures
 * are logged but do not break an already-saved settlement during the rewrite
 * transition.
 */
class SettlementFinalSavePostingConnector
{
    public static function postAfterFinalize($settlement, array $requestData = []): array
    {
        try {
            $context = static::buildContext($settlement, $requestData);

            return SettlementPostingFacade::post($context);
        } catch (\Throwable $e) {
            Log::error('PD Settlement rewrite posting connector failed', [
                'settlement_id' => $settlement->id ?? null,
                'settlement_no' => $settlement->settlement_no ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'posted' => [],
                'skipped' => [],
                'warnings' => [[
                    'area' => 'posting_connector',
                    'message' => $e->getMessage(),
                ]],
            ];
        }
    }

    protected static function buildContext($settlement, array $requestData): array
    {
        $businessId = (int) ($settlement->business_id ?? 0);
        $settlementNo = (string) ($settlement->settlement_no ?? '');
        $shiftId = $requestData['shift_id'] ?? $requestData['shift_ids'][0] ?? null;
        $shiftNumber = $requestData['shift_number'] ?? $settlement->shift_number ?? null;

        $savedTotalsReader = new SettlementSavedTotalsReader();
        $consistencyChecker = new SettlementConsistencyChecker($savedTotalsReader);
        $savedTotals = $savedTotalsReader->totals($settlement);
        $consistency = $consistencyChecker->check($settlement);

        return [
            'business_id' => $businessId,
            'settlement_id' => $settlement->id ?? null,
            'settlement_no' => $settlementNo,
            'saved_totals' => $savedTotals,
            'consistency' => $consistency,
            'pump_operator_id' => $settlement->pump_operator_id ?? ($requestData['pump_operator_id'] ?? null),
            'shift_id' => $shiftId,
            'shift_number' => $shiftNumber,
            'settlement_date' => $settlement->transaction_date ?? $settlement->settlement_date ?? date('Y-m-d'),
            'payment_rows' => static::collectPaymentRows($businessId, $settlementNo),
            'cash_payment_rows' => static::collectSettlementPaymentRows('settlement_cash_payments', $businessId, $settlement->id ?? null, $settlementNo),
            'card_payment_rows' => static::collectSettlementPaymentRows('settlement_card_payments', $businessId, $settlement->id ?? null, $settlementNo),
            'created_by' => auth()->id(),
            'meter_rows' => static::collectRows('pumper_day_entries', $businessId, $settlementNo),
            'other_sale_rows' => static::collectRows('pump_operator_other_sales', $businessId, $settlementNo),
            'customer_ledger_rows' => static::collectCustomerLedgerRows($businessId, $settlementNo),
            'pump_operator_ledger_rows' => static::collectPumpOperatorLedgerRows($businessId, $settlementNo),
        ];
    }

    protected static function collectPaymentRows(int $businessId, string $settlementNo): array
    {
        if (! static::tableExists('pump_operator_payments') || $businessId <= 0 || $settlementNo === '') {
            return [];
        }

        return DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where(function ($query) use ($settlementNo) {
                $query->where('settlement_no', $settlementNo)
                    ->orWhere('settlement_no', (string) $settlementNo);
            })
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    protected static function collectSettlementPaymentRows(string $table, int $businessId, $settlementId, string $settlementNo): array
    {
        if (! static::tableExists($table) || $businessId <= 0) {
            return [];
        }

        $query = DB::table($table)->where('business_id', $businessId);

        if (static::columnExists($table, 'settlement_no')) {
            $query->where(function ($q) use ($settlementId, $settlementNo) {
                if (! empty($settlementId)) {
                    $q->orWhere('settlement_no', $settlementId)
                      ->orWhere('settlement_no', (string) $settlementId);
                }

                if ($settlementNo !== '') {
                    $q->orWhere('settlement_no', $settlementNo);
                }
            });
        } elseif (static::columnExists($table, 'settlement_id')) {
            if (empty($settlementId)) {
                return [];
            }

            $query->where('settlement_id', $settlementId);
        } else {
            return [];
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    protected static function collectRows(string $table, int $businessId, string $settlementNo): array
    {
        if (! static::tableExists($table) || $businessId <= 0 || $settlementNo === '') {
            return [];
        }

        $query = DB::table($table)->where('business_id', $businessId);

        if (static::columnExists($table, 'settlement_no')) {
            $query->where('settlement_no', $settlementNo);
        } elseif (static::columnExists($table, 'settlement_id')) {
            return [];
        } else {
            return [];
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    protected static function collectCustomerLedgerRows(int $businessId, string $settlementNo): array
    {
        return static::collectRows('contact_ledgers', $businessId, $settlementNo);
    }

    protected static function collectPumpOperatorLedgerRows(int $businessId, string $settlementNo): array
    {
        foreach (['pump_operator_ledgers', 'petro_pump_operator_ledgers'] as $table) {
            $rows = static::collectRows($table, $businessId, $settlementNo);
            if (! empty($rows)) {
                return $rows;
            }
        }

        return [];
    }

    protected static function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected static function columnExists(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
