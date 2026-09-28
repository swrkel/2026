<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DayCountSetting extends Model
{
    use HasFactory;

    // protected $fillable = [];
    protected $guarded = ['id'];
    
    protected static function newFactory()
    {
        return \Modules\PetroPD\Database\factories\DayCountSettingFactory::new();
    }
}
