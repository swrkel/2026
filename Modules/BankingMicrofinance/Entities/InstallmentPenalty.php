<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class InstallmentPenalty extends Model { use SoftDeletes; protected $table = 'bkg_mfi_installment_penalties'; protected $guarded = ['id']; }
