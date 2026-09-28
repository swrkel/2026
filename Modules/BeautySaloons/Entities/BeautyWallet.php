<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyWallet extends Model
{
    protected $table = 'bs_wallets';
    protected $guarded = ['id'];

    public function transactions()
    {
        return $this->hasMany(BeautyWalletTransaction::class, 'wallet_id');
    }
}
