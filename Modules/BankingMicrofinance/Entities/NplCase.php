<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class NplCase extends Model { use SoftDeletes; protected $table='bkg_mfi_npl_cases'; protected $guarded=[]; }
