<?php

namespace Modules\DistributionNew\Entities\OperationalPolish;

use Illuminate\Database\Eloquent\Model;

class ReconciliationException extends Model
{
    protected $table = 'disnew_reconciliation_exceptions';
    protected $guarded = ['id'];
}
