<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\POS\Services\ReceiptPrintService;

class ReceiptPrintController extends Controller
{
    public function __construct(private ReceiptPrintService $receiptService) {}

    public function print($saleId)
    {
        return view('pos::sales.receipt', $this->receiptService->buildReceipt($saleId));
    }

    public function reprint($saleId)
    {
        $this->receiptService->recordReprint($saleId);
        return redirect()->route('pos.receipts.print', $saleId);
    }
}
