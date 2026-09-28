<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ReservationRoom extends Model
{
    use SoftDeletes;
    protected $table = 'hm_reservation_rooms';
    protected $guarded = ['id'];
}
