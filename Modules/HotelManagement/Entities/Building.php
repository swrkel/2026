<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Building extends Model
{
    use SoftDeletes;
    protected $table = 'hm_buildings';
    protected $guarded = ['id'];
}
