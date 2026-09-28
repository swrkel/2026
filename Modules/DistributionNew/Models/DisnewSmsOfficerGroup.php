<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewSmsOfficerGroup extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_sms_officer_groups';
    protected $guarded = ['id'];
}
