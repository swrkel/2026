<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsNewSetting extends Model
{
    protected $table = 'leads_new_settings';
    protected $guarded = ['id'];
    protected $casts = ['value' => 'array'];

    public static function getValue(string $key, $default = null, ?int $businessId = null)
    {
        $row = static::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->where('key', $key)
            ->first();

        return $row ? $row->value : $default;
    }

    public static function setValue(string $key, $value, ?int $businessId = null): self
    {
        return static::query()->updateOrCreate(
            ['business_id' => $businessId, 'key' => $key],
            ['value' => $value]
        );
    }
}
