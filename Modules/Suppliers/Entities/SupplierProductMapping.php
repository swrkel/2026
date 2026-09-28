<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;

class SupplierProductMapping extends Model
{
    protected $table = 'supplier_product_mappings';
    protected $guarded = ['id'];
}
