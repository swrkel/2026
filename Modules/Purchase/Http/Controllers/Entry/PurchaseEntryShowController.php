<?php

namespace Modules\Purchase\Http\Controllers\Entry;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Purchase\Services\Entry\PurchaseEntryShowService;
use Modules\Purchase\Utils\PurchaseAccessUtil;

class PurchaseEntryShowController extends Controller
{
    public function __construct(protected PurchaseAccessUtil $access)
    {
    }

    public function show(int $id, PurchaseEntryShowService $service): View
    {
        abort_unless($this->access->canView(), 403, 'Unauthorized action.');

        return view('purchase::entries.show', $service->pageData($id));
    }
}
