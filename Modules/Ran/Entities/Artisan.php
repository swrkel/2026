<?php

namespace Modules\Ran\Entities;

class Artisan extends RanModel
{
    protected $table = 'ran_artisans';
    protected $casts = ['opening_balance' => 'decimal:4', 'is_active' => 'boolean'];
    public function supplierContact() { return $this->belongsTo(\Modules\Suppliers\Entities\Supplier::class, 'supplier_contact_id'); }

}
