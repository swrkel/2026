<?php

namespace Modules\BankingCheque\Entities;

use Illuminate\Database\Eloquent\Model;

class ChequeStopPayment extends Model
{
    protected $table = 'bkg_cheque_stop_payments';
    protected $guarded = [];
    public function leaf(){ return $this->belongsTo(ChequeLeaf::class, 'cheque_leaf_id'); }
}
