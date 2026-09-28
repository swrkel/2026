<?php

namespace Modules\Membership\Entities;

use App\User;
use App\Business;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MembershipBusinessType extends Model
{
    protected $table = 'membership_business_types';

    protected $fillable = [
        'business_id',
        'business_type',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_membership_business_types', 'membership_business_type_id', 'user_id')
            ->withTimestamps();
    }
}
