<?php

namespace Modules\DistributionNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DisnewSyncItem extends Model
{
    protected $table = 'disnew_sync_items';
    protected $guarded = ['id'];
}
