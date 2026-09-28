<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Purchase\Services\Entry\PurchaseEntryShowService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryPrintController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function print(int $id, PurchaseEntryShowService $service): View
    {
        abort_unless($this->access->canPrint(), 403, 'Unauthorized action.');

        return view('purchase::entries.print', $service->pageData($id));
    }
}
