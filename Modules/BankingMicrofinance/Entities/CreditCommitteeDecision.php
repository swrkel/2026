<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class CreditCommitteeDecision extends Model { use SoftDeletes; protected $table='bkg_mfi_credit_committee_decisions'; protected $guarded=[]; protected $casts=['summary_json'=>'array','is_active'=>'boolean']; }
