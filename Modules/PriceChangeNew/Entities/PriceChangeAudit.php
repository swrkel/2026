<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PriceChangeAudit extends Model
{
    public $timestamps = false;
    protected $table = 'pcn_price_change_audits';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array', 'created_at' => 'datetime'];
}
