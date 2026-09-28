<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

class VatInvoiceToTransactionSetting extends Model
{
    protected $table = 'vat_invoice_to_transaction_settings';
    protected $guarded = ['id'];

    public function created_by_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
