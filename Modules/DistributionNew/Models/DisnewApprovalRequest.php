<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewApprovalRequest extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_approval_requests';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'location_id',
        'rule_id',
        'reference_type',
        'reference_id',
        'requested_by',
        'approved_by',
        'status',
        'requested_at',
        'approved_at',
        'remarks'
    ];
}
