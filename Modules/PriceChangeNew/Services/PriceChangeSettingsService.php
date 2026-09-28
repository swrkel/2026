<?php

namespace Modules\PriceChangeNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PriceChangeNew\Entities\NumberSequence;
use Modules\PriceChangeNew\Entities\PriceChangeSetting;

class PriceChangeSettingsService
{
    public function get(int $businessId, string $key, mixed $default = null): mixed
    {
        $fallbacks = (array) config('pricechangenew.setting_defaults', []);
        $fallback = array_key_exists($key, $fallbacks) ? $fallbacks[$key] : $default;

        $value = PriceChangeSetting::query()
            ->where('business_id', $businessId)
            ->where('location_id', 0)
            ->where('setting_key', $key)
            ->value('setting_value');

        if ($value === null) {
            return $fallback;
        }

        $decoded = json_decode((string) $value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /** @return array<string, mixed> */
    public function all(int $businessId): array
    {
        $settings = (array) config('pricechangenew.setting_defaults', []);
        $rows = PriceChangeSetting::query()
            ->where('business_id', $businessId)
            ->where('location_id', 0)
            ->pluck('setting_value', 'setting_key');

        foreach ($rows as $key => $value) {
            $decoded = json_decode((string) $value, true);
            $settings[(string) $key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        $sequence = NumberSequence::query()->where('business_id', $businessId)->first();
        $settings['reference_prefix'] = $sequence?->prefix ?: 'PCN';
        $settings['reference_padding'] = (int) ($sequence?->padding ?: 6);

        return $settings;
    }

    /** @param array<string, mixed> $settings */
    public function save(int $businessId, array $settings, ?int $userId): void
    {
        DB::transaction(function () use ($businessId, $settings, $userId): void {
            foreach ($settings as $key => $value) {
                if (in_array($key, ['reference_prefix', 'reference_padding'], true)) {
                    continue;
                }

                PriceChangeSetting::query()->updateOrCreate(
                    ['business_id' => $businessId, 'location_id' => 0, 'setting_key' => $key],
                    [
                        'setting_value' => json_encode($value, JSON_UNESCAPED_SLASHES),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]
                );
            }

            $sequence = NumberSequence::query()->firstOrCreate(
                ['business_id' => $businessId],
                ['prefix' => 'PCN', 'next_number' => 1, 'padding' => 6]
            );
            $sequence->prefix = strtoupper(trim((string) ($settings['reference_prefix'] ?? 'PCN'))) ?: 'PCN';
            $sequence->padding = max(3, min(12, (int) ($settings['reference_padding'] ?? 6)));
            $sequence->save();
        }, 3);
    }
}
