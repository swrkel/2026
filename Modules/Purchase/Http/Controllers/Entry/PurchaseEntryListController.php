<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Entry\PurchaseEntryListService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryListController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function index(Request $request, PurchaseEntryListService $service)
    {
        abort_unless($this->access->canView(), 403, 'Unauthorized action.');

        return view('purchase::entries.index', $service->pageData($request));
    }
}
