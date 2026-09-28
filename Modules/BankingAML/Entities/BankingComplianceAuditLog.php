<?php

namespace Modules\BankingAML\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingComplianceAuditLog extends Model
{
    protected $table = 'bkg_compliance_audit_logs';
    protected $guarded = [];
}
