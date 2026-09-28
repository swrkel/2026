<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Reservation extends Model
{
    use SoftDeletes;
    protected $table = 'hm_reservations';
    protected $guarded = ['id'];
}
