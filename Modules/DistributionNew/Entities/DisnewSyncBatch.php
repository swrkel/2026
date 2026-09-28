<?php

namespace Modules\DistributionNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DisnewSyncBatch extends Model
{
    protected $table = 'disnew_sync_batches';
    protected $guarded = ['id'];
}
