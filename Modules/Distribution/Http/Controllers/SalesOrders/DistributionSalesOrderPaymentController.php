<?php

namespace Modules\Distribution\Http\Controllers\SalesOrders;

use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Services\SalesOrders\DistributionSalesOrderPaymentService;

class DistributionSalesOrderPaymentController extends Controller
{
    protected DistributionSalesOrderPaymentService $paymentService;

    public function __construct(DistributionSalesOrderPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function show(int $id)
    {
        $businessId = (int) session()->get('user.business_id');
        $salesOrder = $this->paymentService->findSalesOrder($businessId, $id);
        $rows = $this->paymentService->paymentRows($salesOrder);
        $total = $this->paymentService->totalPaid($rows);
        $balanceDue = $this->paymentService->balanceDue($salesOrder, $total);

        return view('distribution::sales_orders.payments.show', [
            'sales_order' => $salesOrder,
            'rows' => $rows,
            'total' => $total,
            'balance_due' => $balanceDue,
        ]);
    }
}
