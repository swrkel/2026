<?php
namespace Modules\Tailoring\OrderManagement\Services;

use Illuminate\Http\Request;

class TailoringOrderService
{
    public function summary(Request $request): array
    {
        return [
            'orders_today' => 0,
            'pending' => 0,
            'in_production' => 0,
            'ready' => 0,
            'delivered' => 0,
            'outstanding' => 0,
        ];
    }

    public function find($id): array
    {
        return [
            'id' => $id,
            'order_no' => '',
            'customer' => '',
            'items' => [],
            'timeline' => [],
            'outstanding' => 0,
        ];
    }
}
