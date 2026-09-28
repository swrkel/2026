<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class RoomFeature extends Model
{
    use SoftDeletes;
    protected $table = 'hm_room_features';
    protected $guarded = ['id'];
}
