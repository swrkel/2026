<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Floor extends Model
{
    use SoftDeletes;
    protected $table = 'hm_floors';
    protected $guarded = ['id'];
}
