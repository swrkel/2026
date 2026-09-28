<?php

namespace Modules\Purchase\Http\Controllers\Return;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Return\PurchaseReturnListService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseReturnListController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function index(Request $request, PurchaseReturnListService $service)
    {
        abort_unless($this->access->canViewReturns(), 403, 'Unauthorized action.');

        return view('purchase::returns.index', $service->pageData($request));
    }
}
