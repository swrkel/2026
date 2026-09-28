<?php
namespace Modules\BankingMicrofinance\Entities;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class RecoveryCase extends Model { use SoftDeletes;
    protected $table = 'bkg_mfi_recovery_cases';
    protected $guarded = ['id'];
}
