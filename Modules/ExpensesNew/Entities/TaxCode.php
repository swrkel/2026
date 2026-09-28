<?php

namespace Modules\ExpensesNew\Entities;

class TaxCode extends BaseModel
{
    protected $table = 'expnew_tax_codes';
    protected $guarded = ['id'];
    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
