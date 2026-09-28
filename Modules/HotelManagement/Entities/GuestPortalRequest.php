<?php
namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestPortalRequest extends Model
{
    use SoftDeletes;
    protected $table = 'hm_guest_portal_requests';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array'];
}
