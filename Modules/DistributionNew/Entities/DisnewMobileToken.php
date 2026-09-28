<?php

namespace Modules\DistributionNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DisnewMobileToken extends Model
{
    protected $table = 'disnew_mobile_tokens';
    protected $guarded = ['id'];
}
