<?php

namespace Modules\Membership\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;

class MembershipCardSetting extends Model
{
    protected $fillable = [
        'business_id',
        'length',
        'width',
        'created_by',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width' => 'decimal:2',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

