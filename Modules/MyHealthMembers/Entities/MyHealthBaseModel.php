<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

abstract class MyHealthBaseModel extends Model
{
    public function getConnectionName()
    {
        return config('myhealthmembers.central_connection');
    }
}
