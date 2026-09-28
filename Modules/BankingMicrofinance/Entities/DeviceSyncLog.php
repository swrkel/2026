<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class DeviceSyncLog extends Model
{
    protected $table = 'bkg_mfi_device_sync_logs';
    protected $guarded = ['id'];
}
