<?php

namespace Modules\DistributionNew\Entities\CustomerPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPortalUser extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_customer_portal_users';
    protected $guarded = ['id'];
}
