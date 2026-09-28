<?php

namespace Modules\Deposits\Models;

use Illuminate\Database\Eloquent\Model;

class DepositAccount extends Model
{
    protected $table = 'deposit_accounts';
    protected $guarded = ['id'];

    protected $dates = ['opened_on', 'maturity_on', 'closed_on', 'last_interest_posted_on'];

    protected $casts = [
        'auto_renew' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(DepositProduct::class, 'deposit_product_id');
    }

    public function transactions()
    {
        return $this->hasMany(DepositTransaction::class, 'deposit_account_id');
    }

    public function parties()
    {
        return $this->hasMany(DepositAccountParty::class, 'deposit_account_id');
    }

    public function nominees()
    {
        return $this->hasMany(DepositAccountParty::class, 'deposit_account_id')->where('party_type', 'nominee');
    }

    public function beneficiaries()
    {
        return $this->hasMany(DepositAccountParty::class, 'deposit_account_id')->where('party_type', 'beneficiary');
    }
}
