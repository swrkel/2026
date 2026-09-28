<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransferHold extends Model
{
    protected $table = 'stn_transfer_holds';

    protected $fillable = [
        'business_id', 'transfer_id', 'hold_reason', 'held_by', 'held_at',
        'released_by', 'released_at', 'release_note', 'status'
    ];
}
