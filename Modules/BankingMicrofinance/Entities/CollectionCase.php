<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class CollectionCase extends Model { use SoftDeletes;
    protected $table = 'bkg_mfi_collection_cases';
    protected $guarded = ['id'];
}
