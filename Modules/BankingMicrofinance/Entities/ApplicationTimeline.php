<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; 
class ApplicationTimeline extends Model {  protected $table='bkg_mfi_application_timelines'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
