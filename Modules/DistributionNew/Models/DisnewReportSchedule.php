<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewReportSchedule extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_report_schedules';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'location_id',
        'report_key',
        'frequency',
        'recipient_emails',
        'recipient_user_ids',
        'last_run_at',
        'next_run_at',
        'is_active',
        'created_by'
    ];
}
