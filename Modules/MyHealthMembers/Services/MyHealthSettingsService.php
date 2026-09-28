<?php

namespace Modules\MyHealthMembers\Services;

use Modules\MyHealthMembers\Entities\MyHealthSetting;

class MyHealthSettingsService
{
    public function getGroupedSettings(?int $businessId = null): array
    {
        return MyHealthSetting::query()
            ->when($businessId, fn ($query) => $query->where('business_id', $businessId))
            ->orderBy('setting_group')
            ->orderBy('setting_key')
            ->get()
            ->groupBy('setting_group')
            ->map(fn ($items) => $items->keyBy('setting_key'))
            ->toArray();
    }

    public function saveMany(array $settings, ?int $businessId = null, ?int $locationId = null): void
    {
        foreach ($settings as $group => $items) {
            foreach ((array) $items as $key => $value) {
                MyHealthSetting::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'location_id' => $locationId,
                        'setting_group' => $group,
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => is_array($value) ? json_encode($value) : (string) $value,
                        'value_type' => is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'text'),
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
