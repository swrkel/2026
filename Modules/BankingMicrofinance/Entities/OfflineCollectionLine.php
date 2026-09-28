<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class OfflineCollectionLine extends Model
{
    protected $table = 'bkg_mfi_offline_collection_lines';
    protected $guarded = ['id'];
}
