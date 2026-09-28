<?php

namespace Modules\SW\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * IS2256: add SW testing litres to Petro General's existing Tank Transaction
 * JSON responses without duplicating Petro settlements/meter-sales rows.
 *
 * Why this lives in SW:
 * - SW owns sw_settlements/sw_settlement_lines and therefore the authoritative
 *   testing quantity for an SW settlement.
 * - Petro General already has the tank transaction rows because IS2253 posts
 *   the sold quantity through tank_sell_lines.
 * - Creating fake rows in the legacy settlements/meter_sales tables would make
 *   the same SW settlement appear in other Petro screens and risks double
 *   counting. This middleware only augments the two report responses requested
 *   in IS2256; it does not change stock, settlement or Finance data.
 *
 * The service provider attaches this middleware only to
 * TanksTransactionDetailController routes after the route has been matched, so
 * it runs inside the tenant middleware/connection selected for that request.
 */
class AugmentPetroTankTransactions
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (! $request->ajax() || ! $response instanceof JsonResponse) {
            return $response;
        }

        try {
            $action = (string) optional($request->route())->getActionName();

            if (str_ends_with($action, 'TanksTransactionDetailController@index')) {
                return $this->augmentDetails($request, $response);
            }

            if (str_ends_with($action, 'TanksTransactionDetailController@tankTransactionSummary')) {
                return $this->augmentSummary($request, $response);
            }
        } catch (\Throwable $e) {
            // Reporting integration must never make Petro General unusable.
            Log::warning('IS2256 SW tank report augmentation skipped: ' . $e->getMessage());
        }

        return $response;
    }

    protected function augmentDetails($request, JsonResponse $response): JsonResponse
    {
        $payload = $response->getData(true);
        if (! isset($payload['data']) || ! is_array($payload['data']) || empty($payload['data'])) {
            return $response;
        }

        // Type column: keep the value intact, but force it to wrap inside its
        // own compact box even when the host DataTable applies nowrap to cells.
        foreach ($payload['data'] as &$row) {
            if (is_array($row) && array_key_exists('ref_no', $row)) {
                $row['ref_no'] = $this->wrapTypeValue($row['ref_no']);
            }
        }
        unset($row);

        $businessId = $this->businessId();
        if ($businessId <= 0
            || ! $this->tableExists('sw_settlements')
            || ! $this->tableExists('sw_settlement_lines')
            || ! $this->tableExists('pumps')
            || ! $this->columnExists('pumps', 'fuel_tank_id')
            || ! $this->columnExists('sw_settlement_lines', 'testing_qty')) {
            $response->setData($payload);
            return $response;
        }

        $invoiceNos = collect($payload['data'])
            ->pluck('invoice_no')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->unique()
            ->values();

        if ($invoiceNos->isEmpty()) {
            $response->setData($payload);
            return $response;
        }

        $testing = DB::table('sw_settlement_lines as sl')
            ->join('sw_settlements as ss', 'ss.id', '=', 'sl.settlement_id')
            ->join('pumps as p', 'p.id', '=', 'sl.pump_id')
            ->where('ss.business_id', $businessId)
            ->whereIn('ss.settlement_no', $invoiceNos->all())
            ->where('sl.testing_qty', '>', 0)
            ->when(
                $this->columnExists('sw_settlements', 'deleted_at'),
                fn ($q) => $q->whereNull('ss.deleted_at')
            )
            ->groupBy('ss.settlement_no', 'p.fuel_tank_id')
            ->get([
                'ss.settlement_no',
                'p.fuel_tank_id',
                DB::raw('SUM(sl.testing_qty) as testing_qty'),
            ])
            ->keyBy(fn ($r) => (string) $r->settlement_no . ':' . (int) $r->fuel_tank_id);

        if ($testing->isEmpty()) {
            $response->setData($payload);
            return $response;
        }

        foreach ($payload['data'] as &$row) {
            if (! is_array($row)) {
                continue;
            }

            $invoiceNo = trim((string) ($row['invoice_no'] ?? ''));
            $tankId = (int) ($row['fuel_tank_id'] ?? 0);
            if ($invoiceNo === '' || $tankId <= 0) {
                continue;
            }

            $match = $testing->get($invoiceNo . ':' . $tankId);
            if (! $match) {
                continue;
            }

            $qty = round((float) ($match->testing_qty ?? 0), 3);
            $row['testing_qty'] = '<span class="testing_qty_transaction" data-orig-value="' .
                $this->plainNumber($qty) . '">' . number_format($qty, 3, '.', ',') . '</span>';
        }
        unset($row);

        $response->setData($payload);
        return $response;
    }

    protected function augmentSummary($request, JsonResponse $response): JsonResponse
    {
        $payload = $response->getData(true);
        if (! isset($payload['data']) || ! is_array($payload['data']) || empty($payload['data'])) {
            return $response;
        }

        $businessId = $this->businessId();
        if ($businessId <= 0
            || ! $this->tableExists('sw_settlements')
            || ! $this->tableExists('sw_settlement_lines')
            || ! $this->tableExists('pumps')
            || ! $this->columnExists('pumps', 'fuel_tank_id')
            || ! $this->columnExists('sw_settlement_lines', 'testing_qty')) {
            return $response;
        }

        $startDate = $this->dateString($request->input('start_date'));
        $endDate = $this->dateString($request->input('end_date'));
        if ($startDate === null || $endDate === null) {
            return $response;
        }

        $query = DB::table('sw_settlement_lines as sl')
            ->join('sw_settlements as ss', 'ss.id', '=', 'sl.settlement_id')
            ->join('pumps as p', 'p.id', '=', 'sl.pump_id')
            ->where('ss.business_id', $businessId)
            ->whereDate('ss.transaction_date', '>=', $startDate)
            ->whereDate('ss.transaction_date', '<=', $endDate)
            ->where('sl.testing_qty', '>', 0)
            ->when(
                $this->columnExists('sw_settlements', 'deleted_at'),
                fn ($q) => $q->whereNull('ss.deleted_at')
            );

        if ($request->filled('location_id') && $this->columnExists('sw_settlements', 'location_id')) {
            $query->where('ss.location_id', (int) $request->input('location_id'));
        }

        $testing = $query
            ->groupBy(DB::raw('DATE(ss.transaction_date)'), 'p.fuel_tank_id')
            ->get([
                DB::raw('DATE(ss.transaction_date) as activity_date'),
                'p.fuel_tank_id',
                DB::raw('SUM(sl.testing_qty) as testing_qty'),
            ])
            ->keyBy(fn ($r) => (string) $r->activity_date . ':' . (int) $r->fuel_tank_id);

        if ($testing->isEmpty()) {
            return $response;
        }

        foreach ($payload['data'] as &$row) {
            if (! is_array($row)) {
                continue;
            }

            // The Petro summary query selects fuel_tanks.*, so the tank primary
            // key is normally "id". Keep fuel_tank_id as a compatibility path.
            $tankId = (int) ($row['fuel_tank_id'] ?? $row['id'] ?? 0);
            $date = $this->dateString($row['end_date'] ?? null)
                ?? $this->dateString($row['start_date'] ?? null)
                ?? $this->dateString($row['transaction_date'] ?? null);

            if ($tankId <= 0 || $date === null) {
                continue;
            }

            $match = $testing->get($date . ':' . $tankId);
            if (! $match) {
                continue;
            }

            $swTesting = round((float) ($match->testing_qty ?? 0), 3);
            if ($swTesting <= 0) {
                continue;
            }

            /*
             | IS2257: Testing is a REPORTING column only for SW.
             |
             | The previous IS2256 augmenter added SW testing into Purchase In
             | (and Out) to preserve an equation. That is not the required report
             | semantics: the user has a dedicated "Testing In" column and SW
             | testing must not affect Balance Qty at all.
             |
             | Therefore:
             |   - Purchase / Transferred In: unchanged
             |   - Sold / Transferred Out: unchanged
             |   - Balance Qty: unchanged
             |   - Testing In: existing Petro testing + SW testing
            */
            $testingCurrent = $this->numericValue($row['testing_qty'] ?? 0);
            $testingCombined = round($testingCurrent + $swTesting, 3);
            $testingHtml = '<span class="testing_qty" data-orig-value="' .
                $this->plainNumber($testingCombined) . '">' . number_format($testingCombined, 3, '.', ',') . '</span>';

            $row['testing_qty'] = $testingHtml;
            // Compatibility with installations whose DataTable column key is
            // named testing_in instead of testing_qty. Extra JSON keys are harmless.
            $row['testing_in'] = $testingHtml;
            $row['sw_testing_qty'] = $swTesting;
        }
        unset($row);

        $response->setData($payload);
        return $response;
    }

    protected function wrapTypeValue($value): string
    {
        $html = (string) ($value ?? '');
        if ($html === '' || str_contains($html, 'sw-petro-type-two-line')) {
            return $html;
        }

        return '<span class="sw-petro-type-two-line" style="display:inline-block;white-space:normal!important;' .
            'width:92px;max-width:92px;line-height:1.18;overflow-wrap:break-word;vertical-align:top">' .
            $html . '</span>';
    }

    protected function businessId(): int
    {
        foreach ([
            session('user.business_id'),
            session('business.id'),
            optional(auth()->user())->business_id,
        ] as $candidate) {
            $id = (int) $candidate;
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    protected function numericValue($value): float
    {
        $text = (string) ($value ?? '0');
        if (preg_match('/data-orig-value=["\']([^"\']+)["\']/i', $text, $match)) {
            return (float) str_replace(',', '', $match[1]);
        }

        $text = strip_tags($text);
        $text = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $text));
        return is_numeric($text) ? (float) $text : 0.0;
    }

    protected function plainNumber(float $value): string
    {
        return number_format($value, 3, '.', '');
    }

    protected function dateString($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_array($value)) {
            $value = $value['date'] ?? $value['formatted'] ?? null;
        }

        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        // SQL/ISO dates first.
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $m)) {
            return $m[1];
        }

        foreach (['d/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // Try the next known UI format.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function tableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            ) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
