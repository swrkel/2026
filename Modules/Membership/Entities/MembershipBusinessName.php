<?php
namespace Modules\Membership\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class MembershipBusinessName extends Model
{
    protected $fillable = [
        'business_id',
        'membership_business_type_id',
        'business_name',
        'created_by',
    ];

    public function businessType()
    {
        return $this->belongsTo(MembershipBusinessType::class, 'membership_business_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
