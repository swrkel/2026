<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class ApprovalMatrix extends Model { use SoftDeletes; protected $table='bkg_mfi_approval_matrixs'; protected $guarded=[]; }
