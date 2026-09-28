<?php

namespace Modules\DistributionNew\Entities\CustomerPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerReturnRequest extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_customer_return_requests';
    protected $guarded = ['id'];
}
