<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceInspection extends Model
{
    use SoftDeletes;
    protected $table='auto_service_inspections';
    protected $guarded=[];
    public function items(){ return $this->hasMany(AutoServiceInspectionItem::class,'inspection_id'); }
}
