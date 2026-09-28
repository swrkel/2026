<?php

namespace Modules\SimpleAudit\Models;

use Illuminate\Database\Eloquent\Model;

abstract class SimpleAuditModel extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function onTenantConnection($connection)
    {
        return $this->setConnection($connection);
    }
}
