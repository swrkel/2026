<?php

namespace Modules\POS\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class POSMultiplePaymentService
{
    public function validatePayments(float $billTotal, array $payments): array
    {
        $rows = collect($payments)->filter(fn ($row) => (float)($row['amount'] ?? 0) > 0)->values();
        $paid = round((float)$rows->sum(fn ($row) => (float)$row['amount']), 4);
        $balance = round($billTotal - $paid, 4);

        if ($paid <= 0) {
            throw new InvalidArgumentException('At least one payment row is required.');
        }
        if ($balance < 0) {
            throw new InvalidArgumentException('Paid amount cannot exceed bill total.');
        }

        return [
            'rows' => $rows->map(function ($row, $index) {
                return [
                    'payment_row_no' => $index + 1,
                    'payment_method' => $row['payment_method'] ?? 'cash',
                    'payment_account_id' => $row['payment_account_id'] ?? null,
                    'reference_no' => $row['reference_no'] ?? null,
                    'card_type' => $row['card_type'] ?? null,
                    'amount' => round((float)$row['amount'], 4),
                    'note' => $row['note'] ?? null,
                ];
            })->all(),
            'paid' => $paid,
            'balance' => $balance,
            'is_fully_paid' => abs($balance) < 0.0001,
        ];
    }
}
