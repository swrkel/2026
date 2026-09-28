<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    protected $table = 'reo_number_sequences';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'scope_key', 'document_key',
        'prefix', 'next_number', 'created_by', 'updated_by',
    ];

    protected $casts = ['next_number' => 'integer'];
}
