<?php

namespace Modules\ExpensesNew\Entities;

class TaxRate extends BaseModel
{
    protected $table = 'expnew_tax_rates';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
