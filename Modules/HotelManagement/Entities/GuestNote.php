<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestNote extends Model
{
    
    protected $table = 'hm_guest_notes';
    protected $guarded = ['id'];
}
