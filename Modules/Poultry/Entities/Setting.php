<?php

namespace Modules\Poultry\Entities;

use Modules\Poultry\Support\BusinessContext;

/** Per-tenant key/value settings for the module. */
class Setting extends PoultryModel
{
    protected $table = 'poultry_settings';

    public $timestamps = true;

    public static function get($key, $default = null, $businessId = null)
    {
        $businessId = $businessId ?: BusinessContext::id();

        $row = static::query()
            ->where('business_id', $businessId)
            ->where('key', $key)
            ->first();

        if (! $row) {
            return $default !== null ? $default : config('poultry.defaults.'.$key);
        }

        $decoded = json_decode($row->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
    }

    public static function put($key, $value, $businessId = null)
    {
        $businessId = $businessId ?: BusinessContext::id();

        return static::updateOrCreate(
            ['business_id' => $businessId, 'key' => $key],
            ['value' => is_scalar($value) ? $value : json_encode($value)]
        );
    }
}
