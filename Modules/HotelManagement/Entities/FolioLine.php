<?php

namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FolioLine extends Model
{
    
    protected $table = 'hm_folio_lines';
    protected $guarded = ['id'];
}
