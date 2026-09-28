<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewInstallationStep extends Model
{
    protected $table = 'disnew_installation_steps';

    protected $guarded = ['id'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
