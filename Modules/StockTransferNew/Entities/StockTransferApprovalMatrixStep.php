<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;
class StockTransferApprovalMatrixStep extends Model{use SoftDeletes;protected $table='stnew_approval_matrix_steps';protected $guarded=['id'];protected $casts=['is_mandatory'=>'boolean'];public function matrix(){return $this->belongsTo(StockTransferApprovalMatrix::class,'matrix_id');}}
