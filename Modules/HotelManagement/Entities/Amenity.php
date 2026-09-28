<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Amenity extends Model
{
    use SoftDeletes;
    protected $table = 'hm_amenities';
    protected $guarded = ['id'];
}
