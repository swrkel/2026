<?php

namespace Modules\ExpensesNew\Entities;

class VendorMetric extends BaseModel
{
    protected $table = 'expnew_vendor_metrics';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
