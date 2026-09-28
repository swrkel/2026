<?php

namespace Modules\DistributionNew\Entities\ProductionCompletion;

use Illuminate\Database\Eloquent\Model;

class StockReconciliationRun extends Model
{
    protected $table = 'disnew_stock_reconciliation_runs';
    protected $guarded = ['id'];
}
