<?php
namespace Modules\TeaEstateManagement\Entities;

use Illuminate\Database\Eloquent\Model;

abstract class BaseTeaModel extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
    public function scopeForBusiness($query, int $businessId) { return $query->where($this->getTable().'.business_id', $businessId); }
    public function scopeForLocation($query, ?int $locationId) { return $locationId ? $query->where($this->getTable().'.location_id', $locationId) : $query; }
}
