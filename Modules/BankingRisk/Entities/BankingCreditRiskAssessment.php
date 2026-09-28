<?php

namespace Modules\BankingRisk\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingCreditRiskAssessment extends Model
{
    protected $table = 'bkg_credit_risk_assessments';
    protected $guarded = [];
}
