<?php

namespace Modules\DistributionNew\Entities\ProductionCompletion;

use Illuminate\Database\Eloquent\Model;

class StockReconciliationLine extends Model
{
    protected $table = 'disnew_stock_reconciliation_lines';
    protected $guarded = ['id'];
}
