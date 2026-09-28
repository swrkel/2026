<?php

namespace Modules\CommunicationHub\Services\Settings;

use Modules\CommunicationHub\Entities\CommunicationHubSetting;

class CommunicationHubSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return CommunicationHubSetting::query()->where('key', $key)->value('value') ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function set(string $key, mixed $value, ?string $group = null): void
    {
        CommunicationHubSetting::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'group' => $group]
        );
    }
}
