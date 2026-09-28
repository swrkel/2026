<?php

namespace Modules\ReportsCustomized\Entities;

use Illuminate\Database\Eloquent\Model;

class SavedLiocStatement extends Model
{
    protected $table = 'saved_lioc_statements';

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_ids' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'float',
    ];
}
