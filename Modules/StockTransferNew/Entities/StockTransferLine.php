<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTransferLine extends Model{protected $table='stnew_stock_transfer_lines';protected $guarded=['id'];protected $casts=['qty_requested'=>'decimal:4','qty_dispatched'=>'decimal:4','qty_received'=>'decimal:4','unit_cost'=>'decimal:4','line_total'=>'decimal:4'];public function transfer(){return $this->belongsTo(StockTransfer::class,'transfer_id');}}
