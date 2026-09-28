<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewReturnLine extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_return_lines';
    protected $guarded = ['id'];
}
