<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Season extends Model
{
    use SoftDeletes;
    protected $table = 'hm_seasons';
    protected $guarded = ['id'];
}
