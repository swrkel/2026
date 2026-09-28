<?php

namespace Modules\Ran\Entities;

class Setting extends RanModel
{
    protected $table = 'ran_settings';
    protected $casts = ['value' => 'json'];
}
