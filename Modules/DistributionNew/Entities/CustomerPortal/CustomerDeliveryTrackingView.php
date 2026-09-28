<?php

namespace Modules\DistributionNew\Entities\CustomerPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDeliveryTrackingView extends Model
{

    protected $table = 'disnew_customer_delivery_tracking_views';
    protected $guarded = ['id'];
}
