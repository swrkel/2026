<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PosHoldsFallbackController extends Controller
{
    /**
     * Render the standalone POS Holds page even when the POS module provider
     * has not been registered by the module loader.
     */
    public function index(): View
    {
        $views = base_path('Modules/POS/Resources/views');
        $translations = base_path('Modules/POS/Resources/lang');

        if (is_dir($views)) {
            app('view')->addNamespace('pos', $views);
        }

        if (is_dir($translations)) {
            app('translator')->addNamespace('pos', $translations);
        }

        abort_unless(view()->exists('pos::holds.index'), 404, 'POS Holds view is missing.');

        return view('pos::holds.index');
    }
}
