<?php

namespace Modules\Membership\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MembershipDividend extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'member_id',
        'dividend_date',
        'dividend_amount',
        'reference_number',
        'notes',
        'is_checked',
        'checked_by',
        'checked_at',
        'is_approved',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'dividend_date' => 'date',
        'dividend_amount' => 'decimal:2',
        'is_checked' => 'boolean',
        'is_approved' => 'boolean',
        'checked_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the member that owns the dividend.
     */
    public function member()
    {
        return $this->belongsTo(MembershipMember::class, 'member_id');
    }

    /**
     * Get the user who created the dividend.
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Get the user who checked the dividend.
     */
    public function checkedBy()
    {
        return $this->belongsTo(\App\User::class, 'checked_by');
    }

    /**
     * Get the user who approved the dividend.
     */
    public function approvedBy()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }
}

