<?php

namespace Modules\BankingRisk\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingLiquidityRiskSnapshot extends Model
{
    protected $table = 'bkg_liquidity_risk_snapshots';
    protected $guarded = [];
}
