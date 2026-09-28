<?php

namespace Modules\Ran\Entities;

class Design extends RanModel
{
    protected $table = 'ran_designs';
    protected $casts = ['specifications' => 'array', 'is_active' => 'boolean'];
}
