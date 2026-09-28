<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; 
class OriginationStage extends Model {  protected $table='bkg_mfi_origination_stages'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
