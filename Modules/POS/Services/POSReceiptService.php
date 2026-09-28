<?php

namespace Modules\POS\Services;

class POSReceiptService
{
    public function receiptPayload($sale, array $payments = []): array
    {
        return [
            'sale' => $sale,
            'payments' => $payments,
            'printed_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
