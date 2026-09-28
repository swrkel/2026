<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class GuestFeedback extends Model
{
    use SoftDeletes;
    protected $table = 'hm_guest_feedback';
    protected $guarded = ['id'];
}
