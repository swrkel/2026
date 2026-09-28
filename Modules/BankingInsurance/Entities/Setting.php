<?php

namespace Modules\BankingInsurance\Entities;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'banking_insurance_settings';
    protected $guarded = ['id'];

    public static function getValue($business_id, $key, $default = null)
    {
        $record = static::where('business_id', $business_id)->where('key', $key)->first();
        return $record ? $record->value : $default;
    }
}
