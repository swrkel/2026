<?php

namespace Modules\BankingRisk\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingStressTestScenario extends Model
{
    protected $table = 'bkg_stress_test_scenarios';
    protected $guarded = [];
}
