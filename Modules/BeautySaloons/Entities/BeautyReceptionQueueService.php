<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyReceptionQueueService extends Model
{
    protected $table = 'bs_reception_queue_services';
    protected $guarded = ['id'];
}
