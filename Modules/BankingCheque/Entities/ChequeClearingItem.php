<?php

namespace Modules\BankingCheque\Entities;

use Illuminate\Database\Eloquent\Model;

class ChequeClearingItem extends Model
{
    protected $table = 'bkg_cheque_clearing_items';
    protected $guarded = [];
}
