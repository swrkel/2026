<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;

class SupplierNote extends Model
{
    protected $table = 'notes';
    protected $guarded = ['id'];
}
