<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewApprovalRule extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_approval_rules';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'location_id',
        'rule_for',
        'min_amount',
        'max_amount',
        'requires_role',
        'requires_user_id',
        'is_active',
        'created_by'
    ];
}
