<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockTransferCarrierInvoiceLine extends Model
{
    protected $table = 'stn_carrier_invoice_lines';

    protected $fillable = [
        'business_id', 'carrier_invoice_id', 'charge_type', 'description',
        'qty', 'rate', 'amount', 'remarks'
    ];
}
