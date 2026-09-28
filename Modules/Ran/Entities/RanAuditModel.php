<?php

namespace Modules\Ran\Entities;

use Illuminate\Database\Eloquent\Model;

abstract class RanAuditModel extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
}
