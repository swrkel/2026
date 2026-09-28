<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewStatusRule extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_status_rules';
    protected $guarded = ['id'];
}
