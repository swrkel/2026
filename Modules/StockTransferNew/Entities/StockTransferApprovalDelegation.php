<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;
class StockTransferApprovalDelegation extends Model{use SoftDeletes;protected $table='stnew_approval_delegations';protected $guarded=['id'];protected $casts=['valid_from'=>'date','valid_to'=>'date','is_active'=>'boolean'];}
