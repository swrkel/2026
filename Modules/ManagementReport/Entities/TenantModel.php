<?php

namespace Modules\ManagementReport\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\ManagementReport\Support\TenantConnection;

abstract class TenantModel extends Model
{
    /**
     * This ERP keeps tenant operational models on the normal mysql connection
     * after changing that connection's database dynamically.
     */
    protected $connection = 'mysql';

    public function getConnectionName()
    {
        return TenantConnection::activate();
    }
}
