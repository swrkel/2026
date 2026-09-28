<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanAuditLog extends Model
{
    protected $table = 'loan_audit_logs';

    protected $guarded = [];
}