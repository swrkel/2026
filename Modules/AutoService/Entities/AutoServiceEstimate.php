<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceEstimate extends Model
{
    use SoftDeletes;
    protected $table='auto_service_estimates';
    protected $guarded=[];
    public function lines(){ return $this->hasMany(AutoServiceEstimateLine::class,'estimate_id'); }
}
