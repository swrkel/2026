<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class KycDocument extends Model { use SoftDeletes; protected $table='bkg_mfi_kyc_documents'; protected $guarded=[]; }
