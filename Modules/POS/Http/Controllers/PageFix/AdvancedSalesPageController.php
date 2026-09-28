<?php

namespace Modules\POS\Http\Controllers\PageFix;

use Illuminate\Routing\Controller;

class AdvancedSalesPageController extends Controller
{
    public function __invoke()
    {
        return response()
            ->view('pos::pagefix.advanced_sales', [
                'title' => 'POS Advanced Sales',
            ])
            ->header('X-POS-Page-Fix', 'V6');
    }
}
