<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PenaltyRule extends Model { use SoftDeletes; protected $table = 'bkg_mfi_penalty_rules'; protected $guarded = ['id']; }
