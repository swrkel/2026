<?php

namespace Modules\Purchase\Http\Controllers\Return;

use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Return\PurchaseReturnShowService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseReturnShowController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function show(int $id, PurchaseReturnShowService $service)
    {
        abort_unless($this->access->canViewReturns(), 403, 'Unauthorized action.');

        return view('purchase::returns.show', $service->details($id));
    }
}
