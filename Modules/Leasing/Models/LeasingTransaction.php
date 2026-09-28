<?php

namespace Modules\Leasing\Models;

use Illuminate\Database\Eloquent\Model;

class LeasingTransaction extends Model
{
    protected $table = 'leasing_transactions';
    protected $guarded = ['id'];

    public function lease_contract()
    {
        return $this->belongsTo(LeaseContract::class, 'leasing_lease_contract_id');
    }
}
