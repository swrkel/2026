<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class CenterCollectionSheet extends Model
{
    protected $table = 'bkg_mfi_center_collection_sheets';
    protected $guarded = ['id'];
}
