<?php

namespace Modules\POS\Http\Controllers\PageFix;

use Illuminate\Routing\Controller;

class KitchenPageController extends Controller
{
    public function __invoke()
    {
        return response()
            ->view('pos::pagefix.kitchen', [
                'title' => 'POS Kitchen Display',
            ])
            ->header('X-POS-Page-Fix', 'V6');
    }
}
