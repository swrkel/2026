<?php

namespace Modules\BankingTreasury\Entities;

use Illuminate\Database\Eloquent\Model;

class TreasuryTransfer extends Model
{
    protected $table = 'bkg_treasury_transfers';
    protected $guarded = [];
}
