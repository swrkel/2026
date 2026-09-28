<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; 
class LoanPricingRule extends Model {  protected $table='bkg_mfi_loan_pricing_rules'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
