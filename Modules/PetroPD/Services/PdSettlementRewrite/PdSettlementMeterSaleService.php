<?php

namespace Modules\PetroPD\Services\PdSettlementRewrite;

use Illuminate\Support\Collection;

class PdSettlementMeterSaleService
{
    public function uniqueRows(Collection $rows): Collection
    {
        return $rows->unique(function ($row) {
            $parts = [
                $row->id ?? null,
                $row->shift_id ?? null,
                $row->pump_id ?? null,
                $row->product_id ?? null,
                $row->starting_meter ?? null,
                $row->closing_meter ?? null,
                $row->qty ?? null,
                $row->price ?? null,
                $row->sub_total ?? $row->amount ?? null,
            ];

            return implode('|', array_map(fn ($value) => (string) $value, $parts));
        })->values();
    }

    public function total(Collection $rows): float
    {
        return round($this->uniqueRows($rows)->sum(function ($row) {
            return (float) ($row->sub_total ?? $row->amount ?? 0);
        }), 4);
    }
}
