<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class RoomType extends Model
{
    use SoftDeletes;
    protected $table = 'hm_room_types';
    protected $guarded = ['id'];
}
