<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewWarehouse extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_warehouses';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'location_id',
    'name',
    'code',
    'manager_id',
    'phone',
    'address',
    'is_active',
    'created_by'
    ];
}
