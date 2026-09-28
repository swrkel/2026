<?php
namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportSnapshot extends Model
{

    protected $table = 'hm_report_snapshots';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array'];
}
