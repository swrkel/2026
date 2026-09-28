<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class ComplianceAlert extends Model { use SoftDeletes; protected $table='bkg_mfi_compliance_alerts'; protected $guarded=[]; }
