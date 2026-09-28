<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class CreditBureauEnquiry extends Model { use SoftDeletes; protected $table='bkg_mfi_credit_bureau_enquiries'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
