<?php

namespace Modules\DistributionNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DisnewDeliveryCheckpoint extends Model
{
    protected $table = 'disnew_delivery_checkpoints';
    protected $guarded = ['id'];
}
