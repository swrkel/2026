<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LegalAction extends Model { 
    protected $table = 'bkg_mfi_legal_actions';
    protected $guarded = ['id'];
}
