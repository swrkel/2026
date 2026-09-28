<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class CollectionAction extends Model { 
    protected $table = 'bkg_mfi_collection_actions';
    protected $guarded = ['id'];
}
