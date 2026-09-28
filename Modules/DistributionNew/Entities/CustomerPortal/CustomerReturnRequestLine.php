<?php

namespace Modules\DistributionNew\Entities\CustomerPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReturnRequestLine extends Model
{

    protected $table = 'disnew_customer_return_request_lines';
    protected $guarded = ['id'];
}
