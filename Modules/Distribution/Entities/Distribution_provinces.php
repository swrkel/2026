<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class Distribution_provinces extends Model
{
    protected $fillable = ['name', 'business_id', 'added_by'];

    protected $guarded = [];
}
