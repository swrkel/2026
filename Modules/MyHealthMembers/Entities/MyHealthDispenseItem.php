<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthDispenseItem extends MyHealthBaseModel
{
    protected $table = 'myhealth_dispense_items';
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function dispense()
    {
        return $this->belongsTo(MyHealthDispense::class, 'dispense_id');
    }

    public function medicine()
    {
        return $this->belongsTo(MyHealthMedicine::class, 'medicine_id');
    }

    public function batch()
    {
        return $this->belongsTo(MyHealthMedicineBatch::class, 'batch_id');
    }
}
