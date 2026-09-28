<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;

class DisnewCustomerAccessToken extends Model
{
    protected $table = 'disnew_customer_access_tokens';
    protected $guarded = ['id'];
}
