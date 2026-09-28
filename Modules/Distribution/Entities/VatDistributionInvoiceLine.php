<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class VatDistributionInvoiceLine extends Model
{
    protected $table = 'vat_distribution_invoice_lines';
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Product::class, 'product_id');
    }

    public function invoice()
    {
        return $this->belongsTo(VatDistributionInvoice::class, 'invoice_id');
    }
}
