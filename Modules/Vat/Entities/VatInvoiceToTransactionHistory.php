<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

class VatInvoiceToTransactionHistory extends Model
{
    protected $table = 'vat_invoice_to_transaction_history';
    protected $guarded = ['id'];

    public function changed_by_user()
    {
        return $this->belongsTo(\App\User::class, 'changed_by');
    }
}
