<?php
namespace Modules\POS\Services;

class ReceiptPrintService
{
    public function buildReceipt($saleId): array
    {
        return ['saleId' => $saleId, 'lines' => [], 'payments' => []];
    }

    public function recordReprint($saleId): void
    {
        // Reprint history is recorded in pos_receipt_reprints by implementation hook.
    }
}
