<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;

class NumberSeriesService
{
    public function next(int $businessId, string $type, string $prefix, int $openingNumber = 1, int $pad = 6): string
    {
        return DB::transaction(function () use ($businessId, $type, $prefix, $openingNumber, $pad) {
            $row = DB::table('rcm_number_series')
                ->where('business_id', $businessId)
                ->where('series_type', $type)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                $number = max(1, $openingNumber);
                DB::table('rcm_number_series')->insert([
                    'business_id' => $businessId,
                    'series_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $number + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $number = max(1, (int) $row->next_number);
                DB::table('rcm_number_series')->where('id', $row->id)->update([
                    'prefix' => $prefix,
                    'next_number' => $number + 1,
                    'updated_at' => now(),
                ]);
            }

            return $this->format($prefix, $number, $pad);
        });
    }

    public function ensure(int $businessId, string $type, string $prefix, int $openingNumber = 1): object
    {
        $openingNumber = max(1, $openingNumber);

        return DB::transaction(function () use ($businessId, $type, $prefix, $openingNumber) {
            $row = DB::table('rcm_number_series')
                ->where('business_id', $businessId)
                ->where('series_type', $type)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                $id = DB::table('rcm_number_series')->insertGetId([
                    'business_id' => $businessId,
                    'series_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $openingNumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return (object) [
                    'id' => $id,
                    'business_id' => $businessId,
                    'series_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $openingNumber,
                ];
            }

            if ((string) $row->prefix !== $prefix) {
                DB::table('rcm_number_series')->where('id', $row->id)->update([
                    'prefix' => $prefix,
                    'updated_at' => now(),
                ]);
                $row->prefix = $prefix;
            }

            return $row;
        });
    }

    /**
     * Reset an unused sequence to its opening number. This is intentionally
     * separate from ensure() so an existing live sequence is never moved
     * backwards accidentally.
     */
    public function resetUnused(int $businessId, string $type, string $prefix, int $openingNumber): object
    {
        $openingNumber = max(1, $openingNumber);

        return DB::transaction(function () use ($businessId, $type, $prefix, $openingNumber) {
            $row = DB::table('rcm_number_series')
                ->where('business_id', $businessId)
                ->where('series_type', $type)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                $id = DB::table('rcm_number_series')->insertGetId([
                    'business_id' => $businessId,
                    'series_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $openingNumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return (object) [
                    'id' => $id,
                    'business_id' => $businessId,
                    'series_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $openingNumber,
                ];
            }

            DB::table('rcm_number_series')->where('id', $row->id)->update([
                'prefix' => $prefix,
                'next_number' => $openingNumber,
                'updated_at' => now(),
            ]);

            $row->prefix = $prefix;
            $row->next_number = $openingNumber;
            return $row;
        });
    }

    public function peek(int $businessId, string $type, string $prefix, int $openingNumber = 1, int $pad = 6): array
    {
        $row = DB::table('rcm_number_series')
            ->where('business_id', $businessId)
            ->where('series_type', $type)
            ->first();

        $next = $row ? max(1, (int) $row->next_number) : max(1, $openingNumber);
        $effectivePrefix = $row && $row->prefix !== null ? (string) $row->prefix : $prefix;

        return [
            'prefix' => $effectivePrefix,
            'next_number' => $next,
            'preview' => $this->format($effectivePrefix, $next, $pad),
        ];
    }

    /**
     * Read many sequence previews with one query. Specs are keyed by any caller
     * label and contain: type, prefix, optional opening and optional pad.
     *
     * @param array<string,array{type:string,prefix:string,opening?:int,pad?:int}> $specs
     * @return array<string,array{prefix:string,next_number:int,preview:string}>
     */
    public function peekMany(int $businessId, array $specs): array
    {
        if (! $specs) {
            return [];
        }

        $types = array_values(array_unique(array_map(
            static fn (array $spec) => (string) $spec['type'],
            $specs
        )));

        $rows = DB::table('rcm_number_series')
            ->where('business_id', $businessId)
            ->whereIn('series_type', $types)
            ->get()
            ->keyBy('series_type');

        $result = [];
        foreach ($specs as $key => $spec) {
            $type = (string) $spec['type'];
            $fallbackPrefix = (string) $spec['prefix'];
            $opening = max(1, (int) ($spec['opening'] ?? 1));
            $pad = max(1, (int) ($spec['pad'] ?? 6));
            $row = $rows->get($type);
            $next = $row ? max(1, (int) $row->next_number) : $opening;
            $prefix = $row && $row->prefix !== null ? (string) $row->prefix : $fallbackPrefix;
            $result[$key] = [
                'prefix' => $prefix,
                'next_number' => $next,
                'preview' => $this->format($prefix, $next, $pad),
            ];
        }

        return $result;
    }

    /**
     * Reserve several independent document numbers in one locking transaction.
     * This is used by Paddy Receiving where Weighbridge, Receipt and Stock Lot
     * numbers are issued together. It avoids the repeated select/update round
     * trips of calling next() three times while keeping row-level locking.
     *
     * @param array<string,array{type:string,prefix:string,opening?:int,pad?:int}> $specs
     * @return array<string,string>
     */
    public function nextMany(int $businessId, array $specs): array
    {
        if (! $specs) {
            return [];
        }

        return DB::transaction(function () use ($businessId, $specs) {
            $normalized = [];
            foreach ($specs as $key => $spec) {
                $normalized[$key] = [
                    'type' => (string) $spec['type'],
                    'prefix' => (string) $spec['prefix'],
                    'opening' => max(1, (int) ($spec['opening'] ?? 1)),
                    'pad' => max(1, (int) ($spec['pad'] ?? 6)),
                ];
            }

            $types = array_values(array_unique(array_column($normalized, 'type')));
            abort_unless(count($types) === count($normalized), 500, 'Duplicate Rice Mill number-series type requested.');

            $now = now();
            $existing = DB::table('rcm_number_series')
                ->where('business_id', $businessId)
                ->whereIn('series_type', $types)
                ->orderBy('series_type')
                ->lockForUpdate()
                ->get()
                ->keyBy('series_type');

            $missing = [];
            foreach ($normalized as $spec) {
                if (! $existing->has($spec['type'])) {
                    $missing[] = [
                        'business_id' => $businessId,
                        'series_type' => $spec['type'],
                        'prefix' => $spec['prefix'],
                        'next_number' => $spec['opening'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($missing) {
                DB::table('rcm_number_series')->insertOrIgnore($missing);
                $existing = DB::table('rcm_number_series')
                    ->where('business_id', $businessId)
                    ->whereIn('series_type', $types)
                    ->orderBy('series_type')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('series_type');
            }

            $result = [];
            foreach ($normalized as $key => $spec) {
                $row = $existing->get($spec['type']);
                abort_unless($row, 500, 'Unable to initialize Rice Mill number series.');
                $number = max(1, (int) $row->next_number);
                DB::table('rcm_number_series')->where('id', $row->id)->update([
                    'prefix' => $spec['prefix'],
                    'next_number' => $number + 1,
                    'updated_at' => $now,
                ]);
                $result[$key] = $this->format($spec['prefix'], $number, $spec['pad']);
            }

            return $result;
        });
    }

    /**
     * Set the next number for a sequence without issuing a document number.
     * The caller is responsible for applying any domain-specific lower bound
     * (for example, highest transaction number + 1).
     */
    public function setNextNumber(int $businessId, string $type, string $prefix, int $nextNumber): object
    {
        $nextNumber = max(1, $nextNumber);

        return DB::transaction(function () use ($businessId, $type, $prefix, $nextNumber) {
            $row = DB::table('rcm_number_series')
                ->where('business_id', $businessId)
                ->where('series_type', $type)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                $id = DB::table('rcm_number_series')->insertGetId([
                    'business_id' => $businessId,
                    'series_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $nextNumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return (object) [
                    'id' => $id,
                    'business_id' => $businessId,
                    'series_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => $nextNumber,
                ];
            }

            DB::table('rcm_number_series')->where('id', $row->id)->update([
                'prefix' => $prefix,
                'next_number' => $nextNumber,
                'updated_at' => now(),
            ]);

            $row->prefix = $prefix;
            $row->next_number = $nextNumber;
            return $row;
        });
    }

    public function delete(int $businessId, string $type): void
    {
        DB::table('rcm_number_series')
            ->where('business_id', $businessId)
            ->where('series_type', $type)
            ->delete();
    }

    public function format(string $prefix, int $number, int $pad = 6): string
    {
        return $prefix . str_pad((string) max(1, $number), $pad, '0', STR_PAD_LEFT);
    }

    public function varietyLotType(int $varietyId): string
    {
        return 'paddy_lot_variety_' . $varietyId;
    }

    public function varietyLotPrefix(string $documentPrefix, string $varietyCode): string
    {
        $documentPrefix = strtoupper(trim($documentPrefix ?: 'PD'));
        $varietyCode = strtoupper(trim($varietyCode));
        return $documentPrefix . '-' . $varietyCode . '-';
    }
}
