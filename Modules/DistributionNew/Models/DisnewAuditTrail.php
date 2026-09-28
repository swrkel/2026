<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewAuditTrail extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_audit_trails';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'location_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'before_json',
        'after_json',
        'ip_address',
        'user_agent'
    ];
}
