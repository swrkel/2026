<?php

namespace Modules\BankingRisk\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingEarlyWarningSignal extends Model
{
    protected $table = 'bkg_early_warning_signals';
    protected $guarded = [];
}
