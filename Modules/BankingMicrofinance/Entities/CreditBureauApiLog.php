<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; 
class CreditBureauApiLog extends Model {  protected $table='bkg_mfi_credit_bureau_api_logs'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
