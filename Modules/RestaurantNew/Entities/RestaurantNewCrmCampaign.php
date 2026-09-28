<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCrmCampaign extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['filters'=>'array','communication_meta'=>'array','start_date'=>'date','end_date'=>'date'];
}
