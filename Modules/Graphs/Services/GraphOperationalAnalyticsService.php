<?php

namespace Modules\Graphs\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GraphOperationalAnalyticsService
{
    public function dipVariance(
        int $businessId,
        Carbon $startDate,
        Carbon $endDate,
        string $period = 'daily',
        ?int $locationId = null
    ): array {
        [$start, $end] = $this->normaliseRange($startDate, $endDate);
        $period = $this->period($period);
        $buckets = $this->periodBuckets($start, $end, $period);

        $empty = [
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'labels' => array_values($buckets),
            'dip' => array_fill(0, count($buckets), null),
            'system' => array_fill(0, count($buckets), null),
            'variance' => array_fill(0, count($buckets), null),
            'rows' => [],
            'summary' => [
                'dip_stock' => 0.0,
                'system_stock' => 0.0,
                'variance' => 0.0,
                'fuel_loss' => 0.0,
                'tanks' => 0,
                'reading_at' => null,
            ],
        ];

        if (! $businessId || ! Schema::hasTable('dip_readings')) {
            return $empty;
        }

        $dipColumn = $this->firstColumn('dip_readings', ['dip_reading', 'dip_value']);
        $systemColumn = $this->firstColumn('dip_readings', ['fuel_balance_dip_reading', 'current_qty', 'system_qty']);
        $dateColumn = $this->firstColumn('dip_readings', ['date_and_time', 'transaction_date', 'created_at', 'date']);
        $tankColumn = $this->firstColumn('dip_readings', ['tank_id']);

        if (! $dipColumn || ! $systemColumn || ! $dateColumn) {
            return $empty;
        }

        $query = DB::table('dip_readings as dr')
            ->where('dr.business_id', $businessId)
            ->whereDate('dr.' . $dateColumn, '>=', $start->toDateString())
            ->whereDate('dr.' . $dateColumn, '<=', $end->toDateString());

        if ($locationId && Schema::hasColumn('dip_readings', 'location_id')) {
            $query->where('dr.location_id', $locationId);
        }

        $hasFuelTanks = $tankColumn && Schema::hasTable('fuel_tanks');
        if ($hasFuelTanks) {
            $query->leftJoin('fuel_tanks as ft', 'ft.id', '=', 'dr.' . $tankColumn);
        }

        $select = [
            DB::raw('dr.' . $dipColumn . ' AS dip_stock'),
            DB::raw('dr.' . $systemColumn . ' AS system_stock'),
            DB::raw('dr.' . $dateColumn . ' AS reading_at'),
            $tankColumn ? DB::raw('dr.' . $tankColumn . ' AS tank_id') : DB::raw('0 AS tank_id'),
        ];

        if ($hasFuelTanks && Schema::hasColumn('fuel_tanks', 'fuel_tank_number')) {
            $select[] = DB::raw('ft.fuel_tank_number AS tank_name');
        } elseif ($hasFuelTanks && Schema::hasColumn('fuel_tanks', 'name')) {
            $select[] = DB::raw('ft.name AS tank_name');
        } else {
            $select[] = DB::raw('NULL AS tank_name');
        }

        $query->select($select)->orderBy('dr.' . $dateColumn);
        if (Schema::hasColumn('dip_readings', 'id')) {
            $query->orderBy('dr.id');
        }

        $latest = [];
        foreach ($query->get() as $record) {
            $readingAt = $this->parseDate($record->reading_at ?? null);
            if (! $readingAt) {
                continue;
            }

            [$bucketKey] = $this->bucketForDate($readingAt, $period);
            if (! array_key_exists($bucketKey, $buckets)) {
                continue;
            }

            $tankId = (int) ($record->tank_id ?? 0);
            $tankKey = $tankId > 0 ? (string) $tankId : 'all';
            $latest[$bucketKey][$tankKey] = [
                'tank_id' => $tankId,
                'tank' => (string) (($record->tank_name ?? '') ?: ($tankId > 0 ? 'Tank ' . $tankId : 'Tank')),
                'dip_stock' => round((float) ($record->dip_stock ?? 0), 3),
                'system_stock' => round((float) ($record->system_stock ?? 0), 3),
                'reading_at' => $readingAt->format('Y-m-d H:i:s'),
            ];
        }

        $dipSeries = [];
        $systemSeries = [];
        $varianceSeries = [];
        $rows = [];
        $summary = $empty['summary'];
        $lastBucketWithData = null;

        foreach ($buckets as $bucketKey => $label) {
            $bucketRows = array_values($latest[$bucketKey] ?? []);
            if (! $bucketRows) {
                $dipSeries[] = null;
                $systemSeries[] = null;
                $varianceSeries[] = null;
                continue;
            }

            $dipTotal = 0.0;
            $systemTotal = 0.0;
            foreach ($bucketRows as $row) {
                $variance = (float) $row['dip_stock'] - (float) $row['system_stock'];
                $loss = max((float) $row['system_stock'] - (float) $row['dip_stock'], 0);
                $dipTotal += (float) $row['dip_stock'];
                $systemTotal += (float) $row['system_stock'];
                $rows[] = [
                    'period' => $label,
                    'tank' => $row['tank'],
                    'dip_stock' => round((float) $row['dip_stock'], 3),
                    'system_stock' => round((float) $row['system_stock'], 3),
                    'variance' => round($variance, 3),
                    'fuel_loss' => round($loss, 3),
                    'reading_at' => $row['reading_at'],
                ];
            }

            $varianceTotal = $dipTotal - $systemTotal;
            $dipSeries[] = round($dipTotal, 3);
            $systemSeries[] = round($systemTotal, 3);
            $varianceSeries[] = round($varianceTotal, 3);
            $lastBucketWithData = [
                'dip_stock' => round($dipTotal, 3),
                'system_stock' => round($systemTotal, 3),
                'variance' => round($varianceTotal, 3),
                'fuel_loss' => round(max($systemTotal - $dipTotal, 0), 3),
                'tanks' => count($bucketRows),
                'reading_at' => max(array_column($bucketRows, 'reading_at')),
            ];
        }

        if ($lastBucketWithData) {
            $summary = $lastBucketWithData;
        }

        return [
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'labels' => array_values($buckets),
            'dip' => $dipSeries,
            'system' => $systemSeries,
            'variance' => $varianceSeries,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    public function pumpShiftSales(
        int $businessId,
        Carbon $startDate,
        Carbon $endDate,
        string $period = 'daily',
        ?int $locationId = null
    ): array {
        [$start, $end] = $this->normaliseRange($startDate, $endDate);
        $period = $this->period($period);
        $periodBuckets = $this->periodBuckets($start, $end, $period);

        $sourceRows = [];
        $seen = [];

        foreach ($this->operatorMeterRows($businessId, $start, $end, $locationId) as $row) {
            $key = $this->meterLineKey($row);
            $seen[$key] = true;
            $sourceRows[] = $row;
        }
        foreach ($this->regularMeterRows($businessId, $start, $end, $locationId) as $row) {
            $key = $this->meterLineKey($row);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $sourceRows[] = $row;
        }

        $shiftMap = $this->shiftNumberMap($businessId, $sourceRows);
        $axis = [];
        $pumpSeries = [];
        $details = [];
        $pumps = [];
        $shifts = [];
        $totalQty = 0.0;
        $totalAmount = 0.0;

        foreach ($sourceRows as $row) {
            $saleDate = $this->parseDate($row['sale_date'] ?? null);
            if (! $saleDate) {
                continue;
            }
            [$bucketKey, $bucketLabel] = $this->bucketForDate($saleDate, $period);
            if (! array_key_exists($bucketKey, $periodBuckets)) {
                continue;
            }

            $pumpId = (int) ($row['pump_id'] ?? 0);
            $shiftId = (int) ($row['shift_id'] ?? 0);
            $pump = (string) (($row['pump_no'] ?? '') ?: (($row['pump_name'] ?? '') ?: ($pumpId > 0 ? 'Pump ' . $pumpId : 'Pump')));
            $mapKey = $shiftId . '|' . $pumpId;
            $shiftNo = (int) ($shiftMap[$mapKey] ?? $shiftMap[$shiftId . '|0'] ?? 0);
            $shift = $shiftNo > 0 ? 'Shift ' . $shiftNo : ($shiftId > 0 ? 'Shift #' . $shiftId : 'No Shift');
            $axisKey = $bucketKey . '|' . $shift;
            $axisLabel = $bucketLabel . ' · ' . $shift;

            if (! isset($axis[$axisKey])) {
                $axis[$axisKey] = ['bucket' => $bucketKey, 'shift' => $shift, 'label' => $axisLabel];
            }

            $amount = round((float) ($row['amount'] ?? 0), 4);
            $qty = round((float) ($row['qty'] ?? 0), 3);
            $pumpSeries[$pump][$axisKey] = ($pumpSeries[$pump][$axisKey] ?? 0) + $amount;

            $detailKey = $bucketKey . '|' . $pump . '|' . $shift;
            if (! isset($details[$detailKey])) {
                $details[$detailKey] = [
                    'period_key' => $bucketKey,
                    'period' => $bucketLabel,
                    'pump' => $pump,
                    'shift' => $shift,
                    'sold_qty' => 0.0,
                    'sales_amount' => 0.0,
                ];
            }
            $details[$detailKey]['sold_qty'] += $qty;
            $details[$detailKey]['sales_amount'] += $amount;

            $pumps[$pump] = true;
            $shifts[$shiftId > 0 ? (string) $shiftId : $shift] = true;
            $totalQty += $qty;
            $totalAmount += $amount;
        }

        uasort($axis, function (array $a, array $b) {
            $cmp = strcmp($a['bucket'], $b['bucket']);
            return $cmp !== 0 ? $cmp : strnatcasecmp($a['shift'], $b['shift']);
        });
        $axisKeys = array_keys($axis);
        $labels = array_map(fn ($item) => $item['label'], array_values($axis));

        ksort($pumpSeries, SORT_NATURAL | SORT_FLAG_CASE);
        $series = [];
        foreach ($pumpSeries as $pump => $values) {
            $series[] = [
                'name' => $pump,
                'data' => array_map(fn ($key) => round((float) ($values[$key] ?? 0), 4), $axisKeys),
            ];
        }

        $detailRows = array_values($details);
        usort($detailRows, function (array $a, array $b) {
            $cmp = strcmp($a['period_key'], $b['period_key']);
            if ($cmp !== 0) return $cmp;
            $cmp = strnatcasecmp($a['shift'], $b['shift']);
            return $cmp !== 0 ? $cmp : strnatcasecmp($a['pump'], $b['pump']);
        });
        foreach ($detailRows as &$detail) {
            $detail['sold_qty'] = round((float) $detail['sold_qty'], 3);
            $detail['sales_amount'] = round((float) $detail['sales_amount'], 4);
            unset($detail['period_key']);
        }
        unset($detail);

        return [
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'labels' => $labels,
            'series' => $series,
            'rows' => $detailRows,
            'summary' => [
                'sales_amount' => round($totalAmount, 4),
                'sold_qty' => round($totalQty, 3),
                'pumps' => count($pumps),
                'shifts' => count($shifts),
            ],
        ];
    }

    private function operatorMeterRows(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        if (! Schema::hasTable('pump_operator_meter_sales')
            || ! Schema::hasTable('pump_operator_meter_sale_details')
            || ! Schema::hasTable('settlements')
            || ! Schema::hasTable('pumps')
            || ! Schema::hasColumn('pump_operator_meter_sales', 'settlement_no')
            || ! Schema::hasColumn('settlements', 'status')) {
            return [];
        }

        $settlementDate = $this->firstColumn('settlements', ['transaction_date', 'date', 'created_at']);
        if (! $settlementDate) {
            return [];
        }
        $settlementNo = $this->firstColumn('settlements', ['settlement_no', 'settlement_number']);
        $hasSource = Schema::hasColumn('pump_operator_meter_sales', 'source');
        $hasLocationOnPump = Schema::hasColumn('pumps', 'location_id');
        $hasLocationOnSettlement = Schema::hasColumn('settlements', 'location_id');

        $query = DB::table('pump_operator_meter_sale_details as d')
            ->join('pump_operator_meter_sales as m', 'm.id', '=', 'd.sale_id')
            ->join('settlements as s', function ($join) use ($settlementNo) {
                $join->on('s.business_id', '=', 'm.business_id');
                $match = 'CAST(m.settlement_no AS CHAR) = CAST(s.id AS CHAR)';
                if ($settlementNo) {
                    $match .= ' OR CAST(m.settlement_no AS CHAR) = CAST(s.' . $settlementNo . ' AS CHAR)';
                }
                $join->whereRaw('(' . $match . ')');
            })
            ->leftJoin('pumps as p', 'p.id', '=', 'd.pump_id')
            ->where('m.business_id', $businessId)
            ->where('s.status', 0)
            ->whereDate('s.' . $settlementDate, '>=', $start->toDateString())
            ->whereDate('s.' . $settlementDate, '<=', $end->toDateString());

        if ($hasSource) {
            $query->where(function ($q) {
                $q->whereNull('m.source')->orWhere('m.source', 'closing');
            });
        }
        if ($locationId) {
            if ($hasLocationOnSettlement) {
                $query->where('s.location_id', $locationId);
            } elseif ($hasLocationOnPump) {
                $query->where('p.location_id', $locationId);
            }
        }

        return $query->select([
            'd.pump_id',
            'm.shift_id',
            'd.received_meter as starting_meter',
            'd.new_meter as closing_meter',
            'd.sold_qty as qty',
            'd.amount as amount',
            'p.pump_no',
            'p.pump_name',
            DB::raw('s.' . $settlementDate . ' AS sale_date'),
        ])->orderBy('s.' . $settlementDate)->orderBy('d.id')->get()->map(fn ($r) => (array) $r)->all();
    }

    private function regularMeterRows(int $businessId, Carbon $start, Carbon $end, ?int $locationId): array
    {
        if (! Schema::hasTable('meter_sales') || ! Schema::hasTable('pumps')) {
            return [];
        }

        $hasSettlements = Schema::hasTable('settlements') && Schema::hasColumn('settlements', 'status');
        $settlementDate = $hasSettlements ? $this->firstColumn('settlements', ['transaction_date', 'date', 'created_at']) : null;
        $settlementNo = $hasSettlements ? $this->firstColumn('settlements', ['settlement_no', 'settlement_number']) : null;
        $meterDate = $this->firstColumn('meter_sales', ['created_at', 'updated_at']);
        $amountColumn = $this->firstColumn('meter_sales', ['sub_total', 'discount_amount']);
        if (! $meterDate || ! Schema::hasColumn('meter_sales', 'qty')) {
            return [];
        }

        $query = DB::table('meter_sales as ms')->leftJoin('pumps as p', 'p.id', '=', 'ms.pump_id');
        $joinedSettlement = $hasSettlements && $settlementDate && Schema::hasColumn('meter_sales', 'settlement_no');
        if ($joinedSettlement) {
            $query->join('settlements as s', function ($join) use ($settlementNo) {
                $join->on('s.business_id', '=', 'ms.business_id');
                $match = 'CAST(ms.settlement_no AS CHAR) = CAST(s.id AS CHAR)';
                if ($settlementNo) {
                    $match .= ' OR CAST(ms.settlement_no AS CHAR) = CAST(s.' . $settlementNo . ' AS CHAR)';
                }
                $join->whereRaw('(' . $match . ')');
            })->where('s.status', 0);
        }

        $query->where('ms.business_id', $businessId);
        $dateExpression = $joinedSettlement ? 's.' . $settlementDate : 'ms.' . $meterDate;
        $query->whereDate($dateExpression, '>=', $start->toDateString())
            ->whereDate($dateExpression, '<=', $end->toDateString());

        if ($locationId) {
            if ($joinedSettlement && Schema::hasColumn('settlements', 'location_id')) {
                $query->where('s.location_id', $locationId);
            } elseif (Schema::hasColumn('pumps', 'location_id')) {
                $query->where('p.location_id', $locationId);
            }
        }

        if (! $joinedSettlement && Schema::hasColumn('meter_sales', 'settlement_no')) {
            $query->whereNotNull('ms.settlement_no')->where('ms.settlement_no', '<>', '');
        }

        $amountExpression = $amountColumn
            ? 'COALESCE(ms.' . $amountColumn . ', 0)'
            : '(COALESCE(ms.qty,0) * COALESCE(ms.price,0))';

        return $query->select([
            'ms.pump_id',
            'ms.shift_id',
            'ms.starting_meter',
            'ms.closing_meter',
            'ms.qty',
            DB::raw($amountExpression . ' AS amount'),
            'p.pump_no',
            'p.pump_name',
            DB::raw($dateExpression . ' AS sale_date'),
        ])->orderBy($dateExpression)->orderBy('ms.id')->get()->map(fn ($r) => (array) $r)->all();
    }

    private function shiftNumberMap(int $businessId, array $rows): array
    {
        if (! Schema::hasTable('pump_operator_assignments')
            || ! Schema::hasColumn('pump_operator_assignments', 'shift_id')
            || ! Schema::hasColumn('pump_operator_assignments', 'pump_id')
            || ! Schema::hasColumn('pump_operator_assignments', 'shift_number')) {
            return [];
        }

        $shiftIds = array_values(array_unique(array_filter(array_map(fn ($row) => (int) ($row['shift_id'] ?? 0), $rows))));
        if (! $shiftIds) {
            return [];
        }

        $query = DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->whereIn('shift_id', $shiftIds)
            ->select('id', 'shift_id', 'pump_id', 'shift_number')
            ->orderByDesc('id');

        $map = [];
        foreach ($query->get() as $row) {
            $shiftId = (int) $row->shift_id;
            $pumpId = (int) $row->pump_id;
            $shiftNo = (int) $row->shift_number;
            $specific = $shiftId . '|' . $pumpId;
            if (! isset($map[$specific]) && $shiftNo > 0) {
                $map[$specific] = $shiftNo;
            }
            $generic = $shiftId . '|0';
            if (! isset($map[$generic]) && $shiftNo > 0) {
                $map[$generic] = $shiftNo;
            }
        }
        return $map;
    }

    private function meterLineKey(array $row): string
    {
        return implode('|', [
            (int) ($row['pump_id'] ?? 0),
            (int) ($row['shift_id'] ?? 0),
            number_format((float) ($row['starting_meter'] ?? 0), 3, '.', ''),
            number_format((float) ($row['closing_meter'] ?? 0), 3, '.', ''),
            number_format((float) ($row['qty'] ?? 0), 3, '.', ''),
            number_format((float) ($row['amount'] ?? 0), 4, '.', ''),
        ]);
    }

    private function firstColumn(string $table, array $candidates): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }
        foreach ($candidates as $candidate) {
            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function normaliseRange(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();
        if ($end->lt($start)) {
            $tmp = $start;
            $start = $end->copy()->startOfDay();
            $end = $tmp->copy()->endOfDay();
        }
        return [$start, $end];
    }

    private function period(string $period): string
    {
        return in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $period : 'daily';
    }

    private function periodBuckets(Carbon $start, Carbon $end, string $period): array
    {
        $buckets = [];
        if ($period === 'yearly') {
            $cursor = $start->copy()->startOfYear();
            $last = $end->copy()->startOfYear();
            while ($cursor->lte($last)) {
                $buckets[$cursor->format('Y')] = $cursor->format('Y');
                $cursor->addYear();
            }
            return $buckets;
        }
        if ($period === 'weekly') {
            $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
            $last = $end->copy()->startOfWeek(Carbon::MONDAY);
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m-d');
                $buckets[$key] = $cursor->format('d M Y') . ' - ' . $cursor->copy()->addDays(6)->format('d M Y');
                $cursor->addWeek();
            }
            return $buckets;
        }
        if ($period === 'monthly') {
            $cursor = $start->copy()->startOfMonth();
            $last = $end->copy()->startOfMonth();
            while ($cursor->lte($last)) {
                $buckets[$cursor->format('Y-m')] = $cursor->format('M Y');
                $cursor->addMonth();
            }
            return $buckets;
        }

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        while ($cursor->lte($last)) {
            $buckets[$cursor->format('Y-m-d')] = $cursor->format('d M Y');
            $cursor->addDay();
        }
        return $buckets;
    }

    private function bucketForDate(Carbon $date, string $period): array
    {
        if ($period === 'yearly') {
            return [$date->format('Y'), $date->format('Y')];
        }
        if ($period === 'weekly') {
            $start = $date->copy()->startOfWeek(Carbon::MONDAY);
            return [$start->format('Y-m-d'), $start->format('d M Y') . ' - ' . $start->copy()->addDays(6)->format('d M Y')];
        }
        if ($period === 'monthly') {
            return [$date->format('Y-m'), $date->format('M Y')];
        }
        return [$date->format('Y-m-d'), $date->format('d M Y')];
    }

    private function parseDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
