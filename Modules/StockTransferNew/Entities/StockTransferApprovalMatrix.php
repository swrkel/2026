<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;
class StockTransferApprovalMatrix extends Model{use SoftDeletes;protected $table='stnew_approval_matrices';protected $guarded=['id'];protected $casts=['is_active'=>'boolean'];public function steps(){return $this->hasMany(StockTransferApprovalMatrixStep::class,'matrix_id')->orderBy('step_order');}}
