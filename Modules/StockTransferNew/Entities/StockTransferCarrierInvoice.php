<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockTransferCarrierInvoice extends Model
{
    protected $table = 'stn_carrier_invoices';

    protected $fillable = [
        'business_id', 'transfer_id', 'vehicle_id', 'driver_name', 'carrier_name',
        'invoice_no', 'invoice_date', 'freight_amount', 'loading_charge', 'unloading_charge',
        'other_charge', 'tax_amount', 'discount_amount', 'total_amount', 'status',
        'remarks', 'created_by', 'approved_by', 'approved_at', 'cancelled_by', 'cancelled_at'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];
}
