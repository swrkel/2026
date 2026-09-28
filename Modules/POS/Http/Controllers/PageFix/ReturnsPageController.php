<?php

namespace Modules\POS\Http\Controllers\PageFix;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSPageRecoveryService;

class ReturnsPageController extends Controller
{
    public function __invoke(Request $request, POSPageRecoveryService $recovery)
    {
        return response()
            ->view('pos::pagefix.returns', array_merge(
                ['title' => 'POS Returns, Refunds & Exchanges'],
                $recovery->returnsPage($request)
            ))
            ->header('X-POS-Page-Fix', 'V6');
    }
}
