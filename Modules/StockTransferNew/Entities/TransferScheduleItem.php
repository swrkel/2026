<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransferScheduleItem extends Model
{
    protected $table = 'stn_transfer_schedule_items';
    protected $guarded = ['id'];
}
