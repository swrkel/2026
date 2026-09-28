<?php

namespace Modules\Suppliers\Utils;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

/**
 * Suppliers module-local response helper.
 *
 * Controllers should use this wrapper instead of calling application/global
 * response helpers directly. It keeps supplier views, redirects, status
 * flashes and validation returns centralized inside the Suppliers module.
 */
class SupplierResponseUtil
{
    public static function view(string $view, array $data = []): View
    {
        $view = str_starts_with($view, 'suppliers::') ? $view : 'suppliers::' . ltrim($view, ':');

        return view($view, $data);
    }

    public static function backWithStatus(string $messageKey, array $replace = []): RedirectResponse
    {
        return back()->with('status', trans($messageKey, $replace));
    }

    public static function backWithError(string $messageKey, array $replace = []): RedirectResponse
    {
        return back()->with('error', trans($messageKey, $replace));
    }

    public static function route(string $route, array $parameters = [], int $status = 302): RedirectResponse|Redirector
    {
        $route = SupplierRouteUtil::name($route);

        return redirect()->route($route, $parameters, $status);
    }
}
