<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class NightAudit extends Model
{
    protected $table = 'hm_night_audits';
    protected $guarded = [];
}
