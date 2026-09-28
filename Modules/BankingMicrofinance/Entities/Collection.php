<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $table = 'bkg_mfi_collections';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','receipt_no','loan_id','member_id','collection_date','principal_paid','interest_paid','fee_paid','saving_paid','total_paid','payment_method','note','created_by'];
}
