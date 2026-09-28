<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class FieldReceipt extends Model
{
    protected $table = 'bkg_mfi_field_receipts';
    protected $guarded = ['id'];
}
