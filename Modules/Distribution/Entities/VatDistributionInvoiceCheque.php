<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class VatDistributionInvoiceCheque extends Model
{
    protected $table = 'vat_distribution_invoice_cheques';

    protected $fillable = [
        'invoice_id',
        'bank',
        'branch',
        'cheque_no',
        'cheque_date',
        'amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(VatDistributionInvoice::class, 'invoice_id');
    }
}
