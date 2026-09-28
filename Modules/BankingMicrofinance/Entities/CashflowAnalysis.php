<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class CashflowAnalysis extends Model { use SoftDeletes; protected $table='bkg_mfi_cashflow_analyses'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
