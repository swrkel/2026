<?php

namespace Modules\MembershipNew\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipNewCentralMember extends Model
{
    use SoftDeletes;

    protected $table = 'mn_central_members';
    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function businessMaps()
    {
        return $this->hasMany(MembershipNewMemberBusinessMap::class, 'central_member_id');
    }
}
