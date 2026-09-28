<?php

namespace Modules\BankingTellerOperations\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TellerDrawer extends Model
{
    use SoftDeletes;

    protected $table = 'bkg_teller_drawers';
    protected $guarded = ['id'];
    protected $casts = [
        'settings' => 'array',
        'payload' => 'array',
        'denominations' => 'array',
        'rules' => 'array',
        'before_data' => 'array',
        'after_data' => 'array',
        'approved_at' => 'datetime',
        'business_date' => 'date',
        'is_active' => 'boolean',
        'requires_dual_auth' => 'boolean',
    ];
}
