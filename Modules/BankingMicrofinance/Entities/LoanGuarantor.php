<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LoanGuarantor extends Model { use SoftDeletes; protected $table = 'bkg_mfi_loan_guarantors'; protected $guarded = ['id']; }
