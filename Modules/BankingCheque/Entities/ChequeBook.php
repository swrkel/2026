<?php

namespace Modules\BankingCheque\Entities;

use Illuminate\Database\Eloquent\Model;

class ChequeBook extends Model
{
    protected $table = 'bkg_cheque_books';
    protected $guarded = [];
}
