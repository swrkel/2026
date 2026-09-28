<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class CollateralValuation extends Model { use SoftDeletes; protected $table='bkg_mfi_collateral_valuations'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
