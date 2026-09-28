<?php

namespace Modules\BankingInsurance\Entities;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    protected $table = 'banking_insurance_claims';
    protected $guarded = ['id'];
    protected $dates = ['claim_date', 'approved_at'];

    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }
}
