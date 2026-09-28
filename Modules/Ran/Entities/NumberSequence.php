<?php

namespace Modules\Ran\Entities;

class NumberSequence extends RanModel
{
    protected $table = 'ran_number_sequences';
    protected $casts = ['next_number' => 'integer', 'padding' => 'integer', 'last_reset_on' => 'date'];
}
