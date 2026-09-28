<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewSavedFilter extends Model
{
    protected $table = 'disnew_saved_filters';
    protected $guarded = ['id'];
    protected $casts = ['filters' => 'array', 'is_default' => 'boolean'];
}
