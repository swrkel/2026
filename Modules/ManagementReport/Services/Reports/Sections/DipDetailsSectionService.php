<?php

namespace Modules\ManagementReport\Services\Reports\Sections;

use Illuminate\Support\Facades\DB;
use Modules\ManagementReport\Support\ReportContext;
use Modules\ManagementReport\Support\TenantConnection;

class DipDetailsSectionService extends BaseSectionService
{
    public function key()
    {
        return 'dip_details';
    }

    public function build(ReportContext $context)
    {
        if (!$this->schema->table('dip_readings')) {
            return ['rows' => [], 'difference_total' => 0.0, 'as_of_date' => $context->endDate->toDateString()];
        }

        $dip = $this->schema->firstColumn('dip_readings', ['dip_reading', 'dip_value']);
        $system = $this->schema->firstColumn('dip_readings', ['fuel_balance_dip_reading', 'current_qty', 'system_qty']);
        $tank = $this->schema->firstColumn('dip_readings', ['tank_id']);
        $date = $this->schema->firstColumn('dip_readings', ['date_and_time', 'created_at', 'date']);
        if (!$dip || !$system || !$date) {
            return ['rows' => [], 'difference_total' => 0.0, 'as_of_date' => $context->endDate->toDateString()];
        }

        $query = TenantConnection::db()->table('dip_readings');
        if ($tank && $this->schema->table('fuel_tanks')) {
            $query->leftJoin('fuel_tanks', 'dip_readings.' . $tank, '=', 'fuel_tanks.id');
        }

        $this->applyBusinessScope($query, 'dip_readings', $context);
        $query->where('dip_readings.' . $date, '<=', $context->endDate);

        $select = [
            $tank ? DB::raw('dip_readings.' . $tank . ' AS tank_id') : DB::raw('0 AS tank_id'),
            DB::raw('dip_readings.' . $dip . ' AS dip_reading'),
            DB::raw('dip_readings.' . $system . ' AS system_qty'),
            DB::raw('dip_readings.' . $date . ' AS reading_at'),
        ];

        if ($tank && $this->schema->table('fuel_tanks') && $this->schema->column('fuel_tanks', 'fuel_tank_number')) {
            $select[] = DB::raw('fuel_tanks.fuel_tank_number AS tank_name');
        } elseif ($tank && $this->schema->table('fuel_tanks') && $this->schema->column('fuel_tanks', 'name')) {
            $select[] = DB::raw('fuel_tanks.name AS tank_name');
        } else {
            $select[] = DB::raw('NULL AS tank_name');
        }

        $query->select($select)->orderByDesc('dip_readings.' . $date);
        if ($this->schema->column('dip_readings', 'id')) {
            $query->orderByDesc('dip_readings.id');
        }

        // The first row per tank is the actual last reading recorded on or
        // before the historical report end date. MAX(dip) is deliberately not
        // used because the largest reading is not necessarily the latest one.
        $latestByTank = [];
        foreach ($query->get() as $record) {
            $key = $tank ? (string) ((int) $record->tank_id) : 'all';
            if (!array_key_exists($key, $latestByTank)) {
                $latestByTank[$key] = $record;
            }
        }

        $rows = [];
        foreach ($latestByTank as $record) {
            $difference = (float) $record->dip_reading - (float) $record->system_qty;
            $rows[] = [
                'tank' => $record->tank_name ?: ('Tank ' . ((int) $record->tank_id ?: 'N/A')),
                'dip_reading' => (float) $record->dip_reading,
                'system_qty' => (float) $record->system_qty,
                'difference' => $difference,
                'reading_at' => $record->reading_at,
            ];
        }

        return [
            'rows' => $rows,
            'difference_total' => $this->amount(array_sum(array_column($rows, 'difference'))),
            'as_of_date' => $context->endDate->toDateString(),
        ];
    }
}
