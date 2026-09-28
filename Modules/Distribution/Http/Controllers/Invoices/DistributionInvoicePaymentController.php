<?php

namespace Modules\Distribution\Http\Controllers\Invoices;

use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Services\Invoices\DistributionInvoicePaymentService;

class DistributionInvoicePaymentController extends Controller
{
    protected DistributionInvoicePaymentService $paymentService;

    public function __construct(DistributionInvoicePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function show(int $id)
    {
        $businessId = (int) session()->get('user.business_id');
        $invoice = $this->paymentService->findInvoice($businessId, $id);
        $rows = $this->paymentService->paymentRows($invoice);
        $total = $this->paymentService->totalPaid($rows);
        $balanceDue = $this->paymentService->balanceDue($invoice, $total);

        return view('distribution::invoices.payments.show', compact('invoice', 'rows', 'total', 'balanceDue'));
    }
}
