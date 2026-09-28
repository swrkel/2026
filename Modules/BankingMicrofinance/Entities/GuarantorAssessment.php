<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class GuarantorAssessment extends Model { use SoftDeletes; protected $table='bkg_mfi_guarantor_assessments'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
