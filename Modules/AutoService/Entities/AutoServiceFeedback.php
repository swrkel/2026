<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceFeedback extends Model
{
    use SoftDeletes;
    protected $table = 'auto_service_feedback';
    protected $guarded = [];
    public function job(){return $this->belongsTo(AutoServiceJob::class,'job_id');}
    public function vehicle(){return $this->belongsTo(AutoServiceVehicle::class,'vehicle_id');}
}
