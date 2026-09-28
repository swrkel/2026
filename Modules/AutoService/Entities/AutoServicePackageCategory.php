<?php
namespace Modules\AutoService\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AutoServicePackageCategory extends Model
{
    use SoftDeletes;
    protected $table = 'auto_service_package_categories';
    protected $guarded = [];
    public function packages(){ return $this->hasMany(AutoServiceServicePackage::class,'category_id'); }
}
