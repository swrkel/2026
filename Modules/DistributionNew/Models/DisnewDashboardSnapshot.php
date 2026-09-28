<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewDashboardSnapshot extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_dashboard_snapshots';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'location_id',
    'snapshot_date',
    'metric_key',
    'metric_value',
    'metric_amount',
    'created_by'
    ];
}
