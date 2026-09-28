<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;

class SupplierFinancialMovement extends Model
{
    protected $table = 'supplier_financial_movements';
    protected $guarded = ['id'];
}
