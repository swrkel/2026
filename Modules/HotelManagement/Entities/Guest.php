<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Guest extends Model
{
    use SoftDeletes;
    protected $table = 'hm_guests';
    protected $guarded = ['id'];
}
