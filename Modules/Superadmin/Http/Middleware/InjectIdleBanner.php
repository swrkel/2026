<?php

namespace Modules\Superadmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * 8051 - Safely installs the idle-banner browser runtime on full HTML pages.
 *
 * IMPORTANT:
 * Never replace every </body> occurrence in a response. Several legacy pages
 * contain complete HTML documents inside JavaScript print strings/template
 * literals. Replacing those inner closing tags corrupts the surrounding
 * JavaScript. This middleware inserts one external loader only before the LAST
 * closing body tag, which is the real page body terminator.
 *
 * The actual banner code is served separately as JavaScript. Normal business
 * pages therefore receive only one small, deferred <script src="..."> tag.
 */
class InjectIdleBanner
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            if (! auth()->check()
                || $request->ajax()
                || $request->expectsJson()
                || ! $response instanceof Response
                || $response->getStatusCode() !== 200
                || ! method_exists($response, 'getContent')
                || ! method_exists($response, 'setContent')
                || ! Route::has('superadmin.banner.idle.runtime')) {
                return $response;
            }

            $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
            if ($contentType !== '' && strpos($contentType, 'text/html') === false) {
                return $response;
            }

            $content = $response->getContent();
            if (! is_string($content) || $content === '') {
                return $response;
            }

            // Full-page guard: do not touch modal fragments, print fragments,
            // AJAX HTML snippets, downloads or other partial responses.
            if (stripos($content, '<html') === false
                || stripos($content, '<body') === false
                || stripos($content, '</body>') === false
                || stripos($content, '</html>') === false) {
                return $response;
            }

            if (strpos($content, 'data-sa-idle-banner-runtime="1"') !== false) {
                return $response;
            }

            $position = strripos($content, '</body>');
            if ($position === false) {
                return $response;
            }

            $runtimeUrl = route('superadmin.banner.idle.runtime');
            $runtimeUrl = htmlspecialchars($runtimeUrl, ENT_QUOTES, 'UTF-8');

            $loader = "\n<script defer data-sa-idle-banner-runtime=\"1\" src=\"{$runtimeUrl}\"></script>\n";

            // Inject exactly once, immediately before the real/final </body>.
            $content = substr($content, 0, $position)
                . $loader
                . substr($content, $position);

            $response->setContent($content);
            $response->headers->remove('Content-Length');
            $response->headers->remove('ETag');
        } catch (\Throwable $e) {
            // Optional display functionality must never take down a business page.
            Log::debug('Idle banner runtime injection skipped safely.', [
                'message' => $e->getMessage(),
            ]);
        }

        return $response;
    }
}
