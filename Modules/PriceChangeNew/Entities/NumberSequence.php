<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    protected $table = 'pcn_number_sequences';
    protected $guarded = ['id'];
}
