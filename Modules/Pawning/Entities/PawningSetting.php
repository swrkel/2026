<?php

namespace Modules\Pawning\Entities;

use Illuminate\Database\Eloquent\Model;

class PawningSetting extends Model
{
    protected $table = 'pawning_settings';
    protected $guarded = ['id'];
}
