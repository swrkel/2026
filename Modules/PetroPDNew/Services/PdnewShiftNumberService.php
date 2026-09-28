<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PumperDashboardNew\Entities\PoneNumberSequence;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Services\PoneSettingsService;

class PdnewShiftNumberService
{
    public function __construct(private PoneSettingsService $poneSettings) {}

    /**
     * Shift numbers are business-wide. This matches the unique
     * (business_id, shift_number) database rule and prevents two locations from
     * receiving the same number.
     *
     * @return array<string,mixed>
     */
    public function state(int $businessId, int $configuredStartingNumber = 1): array
    {
        $configuredStartingNumber = max(1, $configuredStartingNumber);
        $scopeKey = $this->scopeKey($businessId);
        $sequence = PoneNumberSequence::query()->where('scope_key', $scopeKey)->first();
        $settings = $this->poneSettings->get($businessId, null);
        $prefix = (string) ($sequence?->prefix ?: ($settings['shift_prefix'] ?? 'PONE-SH-'));
        $nextNumber = max($configuredStartingNumber, (int) ($sequence?->next_number ?? $configuredStartingNumber));
        $padding = max(1, (int) ($sequence?->padding ?? 6), strlen((string) $nextNumber));

        return [
            'scope_key' => $scopeKey,
            'configured_starting_number' => $configuredStartingNumber,
            'next_number' => $nextNumber,
            'prefix' => $prefix,
            'padding' => $padding,
            'preview' => $this->format($prefix, $nextNumber, $padding),
        ];
    }

    /**
     * Set the minimum next shift number without ever moving the sequence
     * backwards. Existing shifts are inspected once when settings are saved so
     * a newly introduced sequence cannot duplicate an old shift number.
     *
     * @return array<string,mixed>
     */
    public function applyStartingNumber(int $businessId, int $startingNumber): array
    {
        $startingNumber = max(1, $startingNumber);

        return DB::transaction(function () use ($businessId, $startingNumber): array {
            $scopeKey = $this->scopeKey($businessId);
            $settings = $this->poneSettings->get($businessId, null);
            $prefix = (string) ($settings['shift_prefix'] ?? 'PONE-SH-');
            $minimumNext = max($startingNumber, $this->highestUsedNumericPart($businessId) + 1);
            $padding = max(6, strlen((string) $minimumNext));

            PoneNumberSequence::query()->insertOrIgnore([
                'scope_key' => $scopeKey,
                'business_id' => $businessId,
                'location_id' => null,
                'sequence_type' => 'shift',
                'prefix' => $prefix,
                'next_number' => $minimumNext,
                'padding' => $padding,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = PoneNumberSequence::query()
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->firstOrFail();

            $nextNumber = max((int) $sequence->next_number, $minimumNext);
            $padding = max((int) $sequence->padding, 6, strlen((string) $nextNumber));

            $sequence->forceFill([
                'business_id' => $businessId,
                'location_id' => null,
                'sequence_type' => 'shift',
                'prefix' => $prefix,
                'next_number' => $nextNumber,
                'padding' => $padding,
                'updated_at' => now(),
            ])->save();

            return [
                'scope_key' => $scopeKey,
                'configured_starting_number' => $startingNumber,
                'next_number' => $nextNumber,
                'prefix' => $prefix,
                'padding' => $padding,
                'preview' => $this->format($prefix, $nextNumber, $padding),
            ];
        }, 3);
    }

    private function scopeKey(int $businessId): string
    {
        return $businessId . ':all:shift';
    }

    private function highestUsedNumericPart(int $businessId): int
    {
        $highest = 0;

        PoneShift::query()
            ->where('business_id', $businessId)
            ->whereNotNull('shift_number')
            ->orderBy('id')
            ->pluck('shift_number')
            ->each(function ($shiftNumber) use (&$highest): void {
                if (preg_match('/(\d+)\s*$/', (string) $shiftNumber, $matches) === 1) {
                    $highest = max($highest, (int) $matches[1]);
                }
            });

        return $highest;
    }

    private function format(string $prefix, int $number, int $padding): string
    {
        return $prefix . str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
    }
}
