<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransferLock extends Model
{
    protected $table = 'stn_transfer_locks';
    protected $guarded = ['id'];
    protected $casts = ['locked_at'=>'datetime','released_at'=>'datetime','meta'=>'array'];

    public function scopeActive($query)
    {
        return $query->whereNull('released_at');
    }
}
