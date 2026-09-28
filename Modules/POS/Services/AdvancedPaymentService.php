<?php
namespace Modules\POS\Services;

class AdvancedPaymentService
{
    public function validateMultiplePayments(array $payload): array
    {
        $total = (float) ($payload['total'] ?? 0);
        $paid = collect($payload['payments'] ?? [])->sum(fn ($row) => (float) ($row['amount'] ?? 0));
        return [
            'success' => round($paid, 4) >= round($total, 4),
            'total' => number_format($total, 4, '.', ''),
            'paid' => number_format($paid, 4, '.', ''),
            'balance' => number_format(max($total - $paid, 0), 4, '.', ''),
        ];
    }

    public function reversePayment(array $payload): array
    {
        return ['success' => true, 'reversal_reference' => 'REV-' . now()->format('YmdHis'), 'payload' => $payload];
    }
}
