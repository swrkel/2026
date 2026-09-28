<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ScanSession extends Model
{
    protected $table = 'stock_transfer_new_scan_sessions';
    protected $guarded = ['id'];

    public function lines()
    {
        return $this->hasMany(ScanLine::class, 'scan_session_id');
    }
}
