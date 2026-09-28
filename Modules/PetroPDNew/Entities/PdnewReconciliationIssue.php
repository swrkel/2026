<?php

namespace Modules\PetroPDNew\Entities;

class PdnewReconciliationIssue extends PdnewBaseModel
{
    protected $table = 'pdnew_reconciliation_issues';
    protected $casts = [
        'expected_amount' => 'decimal:4',
        'actual_amount' => 'decimal:4',
        'difference_amount' => 'decimal:4',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }
}
