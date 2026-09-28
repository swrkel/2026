<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BannerTenant extends Model
{
    // Use central database connection
    protected $connection = 'mysql';
    
    protected $table = 'banner_tenants';

    protected $fillable = [
        'banner_id',
        'tenant_id'
    ];

    public $timestamps = false;
}
