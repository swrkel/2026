<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
class AutoServiceJobPackage extends Model
{
    protected $table='auto_service_job_packages';
    protected $guarded=[];
    public function package(){ return $this->belongsTo(AutoServiceServicePackage::class,'package_id'); }
}
