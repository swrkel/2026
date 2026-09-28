<?php

namespace Modules\DistributionNew\Entities\CustomerPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerComplaint extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_customer_complaints';
    protected $guarded = ['id'];
}
