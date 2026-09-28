<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadsNewSavedFilter extends Model
{
    use SoftDeletes;

    protected $table = 'leads_new_saved_filters';

    protected $guarded = ['id'];

    protected $casts = [
        'filters' => 'array',
        'is_default' => 'boolean',
        'is_shared' => 'boolean',
    ];
}
