<?php

namespace Modules\PetroDirectNew\Entities;

use Illuminate\Database\Eloquent\Model;

abstract class PdirectnewBaseModel extends Model
{
    protected $guarded = [];
    protected $casts = [
        'metadata' => 'array',
        'details' => 'array',
    ];
}
