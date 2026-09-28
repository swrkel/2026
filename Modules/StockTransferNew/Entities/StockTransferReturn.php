<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTransferReturn extends Model { protected $table='stnew_transfer_returns'; protected $guarded=['id']; public function transfer(){ return $this->belongsTo(StockTransfer::class,'transfer_id'); } }
