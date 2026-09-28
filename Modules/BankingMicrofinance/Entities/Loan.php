<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $table = 'bkg_mfi_loans';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','loan_no','group_id','member_id','loan_product_id','application_date','approved_date','disbursed_date','principal_amount','interest_amount','fee_amount','total_payable','term_weeks','installment_amount','status','purpose','approval_note','created_by','approved_by'];
}
