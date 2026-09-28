<?php

namespace Modules\BankingRisk\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingOperationalRiskEvent extends Model
{
    protected $table = 'bkg_operational_risk_events';
    protected $guarded = [];
}
