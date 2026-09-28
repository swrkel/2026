<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class RiskAssessment extends Model { use SoftDeletes; protected $table='bkg_mfi_risk_assessments'; protected $guarded=[]; }
