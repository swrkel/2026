<?php

namespace Modules\RestaurantNew\Entities;

class RestaurantNewNumberingSequence extends RestaurantNewBaseModel
{
    protected $table = 'rn_numbering_sequences';

    protected $casts = [
        'reset_yearly' => 'boolean',
    ];
}
