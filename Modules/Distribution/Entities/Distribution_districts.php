<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class Distribution_districts extends Model
{
    protected $fillable = ['name', 'province_id', 'business_id', 'added_by'];

    protected $guarded  = [];
}
