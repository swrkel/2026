<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class Repossession extends Model { 
    protected $table = 'bkg_mfi_repossessions';
    protected $guarded = ['id'];
}
