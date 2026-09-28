<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class OfflineCollectionBatch extends Model
{
    protected $table = 'bkg_mfi_offline_collection_batches';
    protected $guarded = ['id'];
}
