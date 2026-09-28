<?php
namespace Modules\POS\Services;
class POSPaymentService
{
    public function validateMultiplePayments(float $billTotal, array $payments): array
    {
        $paid = array_sum(array_map(fn($p)=>(float)($p['amount'] ?? 0), $payments));
        return ['paid_amount'=>$paid,'balance'=>round($billTotal-$paid,4),'is_fully_paid'=>round($paid,4) >= round($billTotal,4)];
    }
}
