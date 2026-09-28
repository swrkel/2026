<?php

namespace Modules\BankingCheque\Entities;

use Illuminate\Database\Eloquent\Model;

class ChequeClearingBatch extends Model
{
    protected $table = 'bkg_cheque_clearing_batches';
    protected $guarded = [];
}
