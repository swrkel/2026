<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewOperator extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_operators';

    protected $hidden = [
        'passcode_hash',
    ];

    protected $casts = [
        'metadata' => 'array',
        'can_login' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'can_fullscreen' => 'boolean',
        'hide_in_direct_settlement_if_pending_shifts' => 'boolean',
        'dob' => 'date',
        'transaction_date' => 'date',
        'source_updated_at' => 'datetime',
        'opening_balance' => 'decimal:4',
        'commission_value' => 'decimal:4',
        'short_amount' => 'decimal:4',
        'excess_amount' => 'decimal:4',
    ];
}
