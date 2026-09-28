<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyWalletTransaction extends Model
{
    protected $table = 'bs_wallet_transactions';
    protected $guarded = ['id'];

    public function wallet()
    {
        return $this->belongsTo(BeautyWallet::class, 'wallet_id');
    }
}
