<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folio extends Model
{
    use SoftDeletes;
    protected $table = 'hm_folios';
    protected $guarded = ['id'];
}
