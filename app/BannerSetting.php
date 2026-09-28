<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BannerSetting extends Model
{
    // Use central database connection
    protected $connection = 'mysql';
    
    protected $table = 'banner_settings';
    
    protected $fillable = [
        'pause_duration',
        'pause_until'
    ];
}
