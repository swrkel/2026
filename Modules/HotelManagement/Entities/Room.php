<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Room extends Model
{
    use SoftDeletes;
    protected $table = 'hm_rooms';
    protected $guarded = ['id'];
}
