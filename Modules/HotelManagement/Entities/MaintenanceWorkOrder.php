<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceWorkOrder extends Model
{
    use SoftDeletes;
    protected $table = 'hm_maintenance_work_orders';
    protected $guarded = ['id'];
}
