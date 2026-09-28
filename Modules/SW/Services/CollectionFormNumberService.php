<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\NumberSequence;

class CollectionFormNumberService
{
    public function next(int $businessId, int $locationId): string
    {
        if (Schema::hasTable('sw_number_sequences')) {
            return DB::transaction(function () use ($businessId, $locationId) {
                $row = NumberSequence::where('business_id', $businessId)
                    ->where('location_id', $locationId)
                    ->where('document_type', 'collection_form')
                    ->lockForUpdate()->first();
                if (! $row) {
                    $row = NumberSequence::create([
                        'business_id' => $businessId,
                        'location_id' => $locationId,
                        'document_type' => 'collection_form',
                        'prefix' => '',
                        'next_number' => 1,
                    ]);
                }
                $number = (int) $row->next_number;
                $row->next_number = $number + 1;
                $row->save();
                return (string) $number;
            });
        }

        $lock = "sw_collection_no_{$businessId}_{$locationId}";
        $locked = false;
        try {
            $result = DB::selectOne('SELECT GET_LOCK(?, 10) AS got_lock', [$lock]);
            $locked = (int) ($result->got_lock ?? 0) === 1;
            return (string) ($this->legacyHighest($businessId, $locationId) + 1);
        } finally {
            if ($locked) {
                try { DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]); } catch (\Throwable $e) {}
            }
        }
    }

    public function peek(int $businessId, int $locationId): string
    {
        if (Schema::hasTable('sw_number_sequences')) {
            $row = NumberSequence::where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('document_type', 'collection_form')->first();
            return (string) ($row ? (int) $row->next_number : 1);
        }
        return (string) ($this->legacyHighest($businessId, $locationId) + 1);
    }

    protected function legacyHighest(int $businessId, int $locationId): int
    {
        if (! Schema::hasTable('sw_daily_cash') || ! Schema::hasColumn('sw_daily_cash', 'collection_form_no')) {
            return 0;
        }

        $query = DB::table('sw_daily_cash as dc');
        if (Schema::hasTable('sw_shifts') && Schema::hasColumn('sw_daily_cash', 'sw_shift_id')) {
            $query->join('sw_shifts as s', 's.id', '=', 'dc.sw_shift_id')
                ->where('s.business_id', $businessId)->where('s.location_id', $locationId);
        } elseif (Schema::hasColumn('sw_daily_cash', 'business_id')) {
            $query->where('dc.business_id', $businessId);
        }

        $max = 0;
        foreach ($query->pluck('dc.collection_form_no') as $value) {
            if (is_numeric($value)) $max = max($max, (int) $value);
        }
        return $max;
    }
}
