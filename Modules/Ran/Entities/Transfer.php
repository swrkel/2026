<?php

namespace Modules\Ran\Entities;

class Transfer extends RanModel
{
    protected $table = 'ran_transfers';
    protected $casts = ['transfer_date'=>'date','dispatched_at'=>'datetime','received_at'=>'datetime'];
    public function lines(){return $this->hasMany(TransferLine::class);}
}
