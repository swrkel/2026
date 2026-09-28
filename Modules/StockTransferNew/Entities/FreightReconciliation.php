<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class FreightReconciliation extends Model
{
    protected $table = 'stn_freight_reconciliations';
    protected $guarded = ['id'];
}
