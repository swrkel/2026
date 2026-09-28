<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyReceptionQueue extends Model
{
    protected $table = 'bs_reception_queues';
    protected $guarded = ['id'];

    protected $casts = [
        'arrival_at' => 'datetime',
        'check_in_at' => 'datetime',
        'service_start_at' => 'datetime',
        'service_end_at' => 'datetime',
    ];

    public function services()
    {
        return $this->hasMany(BeautyReceptionQueueService::class, 'queue_id');
    }
}
