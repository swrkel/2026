<?php

namespace Modules\BankingUI\Models;

use Illuminate\Database\Eloquent\Model;

class BankingNavigationAudit extends Model
{
    protected $table = 'banking_ui_navigation_audits';

    protected $guarded = ['id'];
}
