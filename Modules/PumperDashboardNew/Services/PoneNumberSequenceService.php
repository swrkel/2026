<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PumperDashboardNew\Entities\PoneNumberSequence;

class PoneNumberSequenceService
{
    public function __construct(private PoneSettingsService $settings) {}

    public function next(int $businessId, ?int $locationId, string $type): string
    {
        return DB::transaction(function () use ($businessId, $locationId, $type): string {
            $scopeKey = $businessId . ':' . ($locationId ?: 'all') . ':' . $type;
            $config = $this->settings->get($businessId, $locationId);
            $prefix = $this->prefix($type, $config);

            $sequence = PoneNumberSequence::query()->where('scope_key', $scopeKey)->lockForUpdate()->first();
            if (! $sequence) {
                PoneNumberSequence::query()->create([
                    'scope_key' => $scopeKey,
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'sequence_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => 1,
                    'padding' => 6,
                ]);
                $sequence = PoneNumberSequence::query()->where('scope_key', $scopeKey)->lockForUpdate()->firstOrFail();
            }

            $number = (int) $sequence->next_number;
            $sequence->update([
                'prefix' => $prefix,
                'next_number' => $number + 1,
            ]);

            return $prefix . str_pad((string) $number, (int) $sequence->padding, '0', STR_PAD_LEFT);
        }, 3);
    }

    /**
     * Allocate an integer sequence while respecting a minimum value discovered
     * from an external/shared table. The row lock keeps concurrent PONE writes
     * from receiving the same number.
     */
    public function nextIntegerAtLeast(
        int $businessId,
        ?int $locationId,
        string $type,
        int $minimum = 1
    ): int {
        return DB::transaction(function () use ($businessId, $locationId, $type, $minimum): int {
            $scopeKey = $businessId . ':' . ($locationId ?: 'all') . ':' . $type;

            PoneNumberSequence::query()->insertOrIgnore([
                'scope_key' => $scopeKey,
                'business_id' => $businessId,
                'location_id' => $locationId,
                'sequence_type' => $type,
                'prefix' => '',
                'next_number' => max(1, $minimum),
                'padding' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = PoneNumberSequence::query()
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->firstOrFail();

            $number = max((int) $sequence->next_number, max(1, $minimum));
            $sequence->forceFill([
                'next_number' => $number + 1,
                'updated_at' => now(),
            ])->save();

            return $number;
        }, 3);
    }

    private function prefix(string $type, array $settings): string
    {
        return match ($type) {
            'shift' => (string) ($settings['shift_prefix'] ?? 'PONE-SH-'),
            'payment' => (string) ($settings['payment_prefix'] ?? 'PONE-PAY-'),
            'other_sale' => (string) ($settings['other_sale_prefix'] ?? 'PONE-OS-'),
            'unload' => (string) ($settings['unload_prefix'] ?? 'PONE-UL-'),
            'settlement' => (string) ($settings['settlement_prefix'] ?? 'PONE-SET-'),
            'collection' => (string) ($settings['collection_prefix'] ?? 'PONE-COL-'),
            'recovery' => (string) ($settings['recovery_prefix'] ?? 'PONE-REC-'),
            'commission' => (string) ($settings['commission_prefix'] ?? 'PONE-COM-'),
            default => 'PONE-' . strtoupper($type) . '-',
        };
    }
}
