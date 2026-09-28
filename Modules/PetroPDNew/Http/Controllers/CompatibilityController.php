<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompatibilityController
{
    /**
     * Redirect the historical /petropdnew URL (and all of its child pages)
     * to the canonical /petro-pd-new route while preserving the query string.
     */
    public function redirect(Request $request, ?string $path = null): RedirectResponse
    {
        $prefix = trim((string) config('petropdnew.route_prefix', 'petro-pd-new'), '/ ');
        $path = trim((string) $path, '/ ');

        $target = '/' . $prefix;

        if ($path !== '') {
            $target .= '/' . $path;
        }

        $query = $request->getQueryString();

        if (is_string($query) && $query !== '') {
            $target .= '?' . $query;
        }

        return redirect()->to($target, 302);
    }
}
