<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthPharmacyStock extends MyHealthBaseModel
{
    protected $table = 'myhealth_pharmacy_stock_ledger';
    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'date',
        'qty_in' => 'decimal:4',
        'qty_out' => 'decimal:4',
        'balance_qty' => 'decimal:4',
    ];

    public function medicine()
    {
        return $this->belongsTo(MyHealthMedicine::class, 'medicine_id');
    }

    public function batch()
    {
        return $this->belongsTo(MyHealthMedicineBatch::class, 'batch_id');
    }
}
