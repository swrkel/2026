<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthMedicineBatch extends MyHealthBaseModel
{
    protected $table = 'myhealth_medicine_batches';
    protected $guarded = ['id'];

    protected $casts = [
        'expiry_date' => 'date',
        'purchase_cost' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'opening_qty' => 'decimal:4',
        'available_qty' => 'decimal:4',
    ];

    public function medicine()
    {
        return $this->belongsTo(MyHealthMedicine::class, 'medicine_id');
    }
}
