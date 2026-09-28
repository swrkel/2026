<?php

namespace Modules\ExpensesNew\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseProfitabilitySnapshot extends Model
{
    use SoftDeletes;

    protected $table = 'expnew_profitability_snapshots';

    protected $guarded = ['id'];
}
