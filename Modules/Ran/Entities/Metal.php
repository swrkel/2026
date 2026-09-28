<?php

namespace Modules\Ran\Entities;

class Metal extends RanModel
{
    protected $table = 'ran_metals';
    protected $casts = ['default_density' => 'decimal:6', 'is_active' => 'boolean'];
}
