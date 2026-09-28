<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServiceServicePackage extends Model
{
    use SoftDeletes;
    protected $table='auto_service_service_packages';
    protected $guarded=[];
    protected $casts=['is_active'=>'boolean'];
    public function lines(){ return $this->hasMany(AutoServicePackageLine::class,'package_id')->orderBy('sort_order'); }
    public function category(){ return $this->belongsTo(AutoServicePackageCategory::class,'category_id'); }
}
