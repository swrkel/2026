<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;

class SupplierActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $guarded = ['id'];
    public $timestamps = false;
}
