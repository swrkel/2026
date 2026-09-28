<?php

namespace Modules\LeadsNew\Support;

class LeadsNewSettingsDefaults
{
    public static function all(): array
    {
        return [
            'lead_prefix' => 'LN',
            'lead_number_padding' => 6,
            'default_status' => 'new',
            'default_priority' => 'medium',
            'default_followup_days' => 1,
            'enable_email_notifications' => false,
            'enable_sms_notifications' => false,
            'enable_auto_assignment' => false,
            'enable_duplicate_check' => true,
            'archive_after_days' => null,
        ];
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        return self::all()[$key] ?? $fallback;
    }
}
