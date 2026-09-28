<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewReturn extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_returns';
    protected $guarded = ['id'];
}
