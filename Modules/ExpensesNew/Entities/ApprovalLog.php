<?php

namespace Modules\ExpensesNew\Entities;

class ApprovalLog extends BaseModel
{
    protected $table = 'expnew_approval_logs';
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:4',
        'meta_json' => 'array',
    ];
}
