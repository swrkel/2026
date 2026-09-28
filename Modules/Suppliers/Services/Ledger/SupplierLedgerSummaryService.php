<?php

namespace Modules\Suppliers\Services\Ledger;

use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierTransaction;
use Modules\Suppliers\Entities\SupplierTransactionPayment;

class SupplierLedgerSummaryService
{
    public function summary(Supplier $supplier): array
    {
        $businessId = (int) \Modules\Suppliers\Utils\SupplierContextUtil::businessId();
        $purchaseTotal = 0;
        $paidTotal = 0;

        if (Schema::hasTable((new SupplierTransaction())->getTable())) {
            $purchaseTotal = (float) SupplierTransaction::query()
                ->where('business_id', $businessId)
                ->where('contact_id', $supplier->id)
                ->where('type', 'purchase')
                ->sum('final_total');
        }

        if (Schema::hasTable((new SupplierTransactionPayment())->getTable()) && Schema::hasTable((new SupplierTransaction())->getTable())) {
            $paidTotal = (float) SupplierTransactionPayment::query()
                ->join('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
                ->where('t.business_id', $businessId)
                ->where('t.contact_id', $supplier->id)
                ->where('t.type', 'purchase')
                ->sum('transaction_payments.amount');
        }

        return [
            'purchase_total' => $purchaseTotal,
            'paid_total' => $paidTotal,
            'balance' => $purchaseTotal - $paidTotal,
        ];
    }
}
