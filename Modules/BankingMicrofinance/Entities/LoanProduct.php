<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    protected $table = 'bkg_mfi_loan_products';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','code','name','min_amount','max_amount','annual_interest_rate','default_term_weeks','interest_method','is_active'];
}
