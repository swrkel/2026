<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;
class StockTransferApprovalStep extends Model{use SoftDeletes;protected $table='stnew_transfer_approval_steps';protected $guarded=['id'];protected $casts=['acted_at'=>'datetime'];public function transfer(){return $this->belongsTo(StockTransfer::class,'transfer_id');}}
