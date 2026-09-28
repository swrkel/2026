<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class RepaymentAllocation extends Model
{
    protected $table = 'bkg_mfi_repayment_allocations';
    protected $guarded = ['id'];
    protected $casts = ['payload'=>'array','is_active'=>'boolean','approved_at'=>'datetime','allocation_date'=>'date','write_off_date'=>'date','recovery_date'=>'date','run_date'=>'date','period_from'=>'date','period_to'=>'date'];
}
