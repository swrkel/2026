<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestPayment extends Model
{
    
    protected $table = 'hm_guest_payments';
    protected $guarded = ['id'];
}
