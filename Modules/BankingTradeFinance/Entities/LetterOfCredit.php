<?php

namespace Modules\BankingTradeFinance\Entities;

use Illuminate\Database\Eloquent\Model;

class LetterOfCredit extends Model
{
    protected $table = 'bkg_tf_letters_of_credit';
    protected $guarded = ['id'];
}
