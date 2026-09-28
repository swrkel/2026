<?php

namespace Modules\Ran\Entities;

class Gemstone extends RanModel
{
    protected $table = 'ran_gemstones';
    protected $casts = ['is_active' => 'boolean'];
}
