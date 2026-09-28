<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransferSchedule extends Model
{
    protected $table = 'stn_transfer_schedules';
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(TransferScheduleItem::class, 'schedule_id');
    }
}
