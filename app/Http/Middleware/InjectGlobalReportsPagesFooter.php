<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Global report/print HTML injection is intentionally disabled.
 *
 * Module-owned print previews must render exactly the HTML/CSS supplied by the
 * module. Automatically adding a generic report header/footer changes page
 * margins, duplicates headings and breaks receipt/statement layouts.
 */
class InjectGlobalReportsPagesFooter
{
    /**
     * Pass every response through unchanged.
     *
     * @param  mixed  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
