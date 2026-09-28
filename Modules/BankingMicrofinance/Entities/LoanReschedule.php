<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LoanReschedule extends Model { use SoftDeletes; protected $table = 'bkg_mfi_loan_reschedules'; protected $guarded = ['id']; }
