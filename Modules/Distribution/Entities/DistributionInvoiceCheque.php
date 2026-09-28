<?php

namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionInvoiceCheque extends Model
{
    protected $table = 'distribution_invoice_cheques';

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
        return $this->belongsTo(DistributionInvoice::class, 'invoice_id');
    }
}

