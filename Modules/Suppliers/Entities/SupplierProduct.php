<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;

class SupplierProduct extends Model
{
    protected $table = 'products';
    protected $guarded = ['id'];
}
