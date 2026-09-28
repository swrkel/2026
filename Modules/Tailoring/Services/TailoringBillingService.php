<?php
namespace Modules\Tailoring\Services;
use Modules\Tailoring\Entities\TailoringPayment;
class TailoringBillingService
{
    public function orderBalance($order): array
    {
        $total=(float)($order->total_amount ?? 0); $paid=(float)TailoringPayment::where('tailoring_order_id',$order->id)->sum('amount');
        return ['total'=>$total,'paid'=>$paid,'balance'=>max($total-$paid,0)];
    }
}
