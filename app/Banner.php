<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Banner extends Model
{
    // Use central database connection
    protected $connection = 'mysql';
    
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'banner_tenants', 'banner_id', 'tenant_id');
    }
}
