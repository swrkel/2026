<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class PromiseToPay extends Model { 
    protected $table = 'bkg_mfi_promises_to_pay';
    protected $guarded = ['id'];
}
