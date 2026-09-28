<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    protected $table = 'bkg_mfi_installments';
    protected $guarded = ['id'];
    protected $fillable = ['loan_id','installment_no','due_date','principal_due','interest_due','fee_due','total_due','paid_amount','status'];
}
