<?php

namespace Modules\BankingInsurance\Entities;

use Illuminate\Database\Eloquent\Model;

class Premium extends Model
{
    protected $table = 'banking_insurance_premiums';
    protected $guarded = ['id'];
    protected $dates = ['payment_date'];

    public function policy()
    {
        return $this->belongsTo(Policy::class, 'policy_id');
    }
}
