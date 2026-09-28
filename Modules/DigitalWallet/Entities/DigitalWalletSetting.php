<?php

namespace Modules\DigitalWallet\Entities;

use Illuminate\Database\Eloquent\Model;

class DigitalWalletSetting extends Model
{
    protected $table = 'digital_wallet_settings';
    protected $guarded = ['id'];
}
