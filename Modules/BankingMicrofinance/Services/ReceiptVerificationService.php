<?php

namespace Modules\BankingMicrofinance\Services;

use Modules\BankingMicrofinance\Entities\FieldReceipt;

class ReceiptVerificationService
{
    public function verify(FieldReceipt $receipt, int $userId): FieldReceipt
    {
        $receipt->update(['status' => 'verified', 'verified_by' => $userId, 'verified_at' => now(), 'exception_note' => null]);
        return $receipt->refresh();
    }

    public function flag(FieldReceipt $receipt, int $userId, ?string $note): FieldReceipt
    {
        $receipt->update(['status' => 'flagged', 'verified_by' => $userId, 'verified_at' => now(), 'exception_note' => $note]);
        return $receipt->refresh();
    }
}
