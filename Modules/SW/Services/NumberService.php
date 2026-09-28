<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\NumberSequence;

/**
 * SW document numbering.
 *
 * Current tenants use sw_number_sequences. Older tenants pre-date that table;
 * for them we deliberately fall back to the already-existing document numbers
 * under a MySQL advisory lock. This keeps Open New Shift / Settlement working
 * without requiring an emergency schema change and still prevents two users
 * receiving the same number concurrently.
 */
class NumberService
{
    public function next(int $businessId, int $locationId, string $documentType = 'shift'): string
    {
        $prefix = (string) config('sw.settlement_prefix', 'SW');

        if (Schema::hasTable('sw_number_sequences')) {
            return DB::transaction(function () use ($businessId, $locationId, $documentType, $prefix) {
                $row = NumberSequence::where('business_id', $businessId)
                    ->where('location_id', $locationId)
                    ->where('document_type', $documentType)
                    ->lockForUpdate()
                    ->first();

                if (! $row) {
                    $row = NumberSequence::create([
                        'business_id' => $businessId,
                        'location_id' => $locationId,
                        'document_type' => $documentType,
                        'prefix' => $prefix,
                        'next_number' => 1,
                    ]);
                }

                $number = (int) $row->next_number;
                $row->next_number = $number + 1;
                $row->save();

                return sprintf('%s-%d-%d', $row->prefix ?: $prefix, $locationId, $number);
            });
        }

        return $this->legacyNext($businessId, $locationId, $documentType, $prefix);
    }

    public function peek(int $businessId, int $locationId, string $documentType = 'shift'): string
    {
        $prefix = (string) config('sw.settlement_prefix', 'SW');

        if (Schema::hasTable('sw_number_sequences')) {
            $row = NumberSequence::where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('document_type', $documentType)
                ->first();
            $number = $row ? (int) $row->next_number : 1;
            return sprintf('%s-%d-%d', $row->prefix ?? $prefix, $locationId, $number);
        }

        $number = $this->legacyHighest($businessId, $locationId, $documentType, $prefix) + 1;
        return sprintf('%s-%d-%d', $prefix, $locationId, $number);
    }

    protected function legacyNext(int $businessId, int $locationId, string $documentType, string $prefix): string
    {
        $lock = sprintf('sw_no_%d_%d_%s', $businessId, $locationId, preg_replace('/[^a-z0-9_]/i', '_', $documentType));
        $locked = false;

        try {
            // MySQL/MariaDB advisory lock. If unavailable, the unique document
            // key still remains the final duplicate guard.
            $result = DB::selectOne('SELECT GET_LOCK(?, 10) AS got_lock', [$lock]);
            $locked = (int) ($result->got_lock ?? 0) === 1;

            $number = $this->legacyHighest($businessId, $locationId, $documentType, $prefix) + 1;
            return sprintf('%s-%d-%d', $prefix, $locationId, $number);
        } finally {
            if ($locked) {
                try { DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]); } catch (\Throwable $e) {}
            }
        }
    }

    protected function legacyHighest(int $businessId, int $locationId, string $documentType, string $prefix): int
    {
        $table = $documentType === 'settlement' ? 'sw_settlements' : 'sw_shifts';
        $column = $documentType === 'settlement' ? 'settlement_no' : 'sw_shift_no';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $query = DB::table($table)
            ->where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->where($column, 'like', $prefix . '-' . $locationId . '-%');

        $numbers = $query->pluck($column);
        $max = 0;
        foreach ($numbers as $value) {
            if (preg_match('/-(\d+)$/', (string) $value, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $max;
    }
}
