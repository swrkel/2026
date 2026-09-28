<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * S714 - Core sidebar interaction and Main Dashboard presentation.
 *
 * The legacy AdminLTE treeview handler animates sidebar submenus. On this
 * installation a second click can race the existing slide animation, so the
 * menu briefly closes/re-opens (the visible "flicker" reported in S714).
 *
 * The current authenticated layouts have one unified sidebar controller. This
 * middleware now injects the historical S714 browser handler only as a fallback
 * for legacy pages that do not render that unified controller. It deliberately
 * does not modify permissions, module visibility, routes, AJAX/JSON responses or
 * any save logic.
 */
class ApplySidebarUiFix
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $this->isHtmlResponse($request, $response)) {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '' || stripos($html, '</body>') === false) {
            return $response;
        }

        if ($request->path() === 'home') {
            $html = $this->addMainDashboardHeading($html);
        }

        $hasSidebar = stripos($html, 'sidebar-menu') !== false || stripos($html, 'main-sidebar') !== false;
        $hasUnifiedSidebarController = strpos($html, 'erp-unified-sidebar-controller-') !== false;

        // Never install a second click owner on pages already using the unified
        // controller. Two capture-phase handlers were the source of the apparent
        // double-click / open-then-close behaviour.
        if ($hasSidebar && ! $hasUnifiedSidebarController && strpos($html, 'data-s714-sidebar-fix') === false) {
            // Inject only before the REAL page closing </body> tag.
            //
            // Do not use str_ireplace() here: some pages contain literal </body>
            // strings inside JavaScript print templates (for example MPCS forms).
            // Replacing every occurrence injects this sidebar <script> inside those
            // JavaScript strings, prematurely closes the outer script, and causes
            // the remaining JavaScript to render as visible page text.
            $bodyClosePosition = strripos($html, '</body>');

            if ($bodyClosePosition !== false) {
                $html = substr($html, 0, $bodyClosePosition)
                    . $this->sidebarFixMarkup()
                    . "\n"
                    . substr($html, $bodyClosePosition);
            }
        }

        $response->setContent($html);
        // An upstream layer may have calculated this from the pre-injection
        // body. Let Symfony/web server recalculate it from the final response.
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function isHtmlResponse(Request $request, $response): bool
    {
        if ($request->ajax() || ! $response instanceof Response) {
            return false;
        }

        if (method_exists($response, 'isRedirection') && $response->isRedirection()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        // Laravel Blade responses can reach this middleware before an explicit
        // content-type is attached; an empty value is safe to inspect as HTML.
        return $contentType === '' || strpos($contentType, 'text/html') !== false;
    }

    /**
     * Put the requested Main Dashboard page heading at the top of /home.
     * Existing headings are respected so this stays idempotent.
     */
    private function addMainDashboardHeading(string $html): string
    {
        if (preg_match('/<h1\b[^>]*>\s*Main\s+Dashboard\s*<\/h1>/i', $html)) {
            return $html;
        }

        // Some historical home templates already render an H1 named Home.
        // Rename that existing heading instead of adding a second one.
        $renamed = preg_replace(
            '/(<h1\b[^>]*>)\s*Home\s*(<\/h1>)/i',
            '$1Main Dashboard$2',
            $html,
            1,
            $renameCount
        );
        if ($renameCount > 0) {
            return $renamed;
        }

        $heading = <<<'HTML'
<section class="content-header s714-main-dashboard-header" data-s714-main-dashboard-heading>
    <h1>Main Dashboard</h1>
</section>
HTML;

        // Standard AdminLTE tenant layout: heading belongs directly inside the
        // content wrapper and before the dashboard content.
        if (preg_match("/<div\\b[^>]*class=([\"'])[^\"']*\\bcontent-wrapper\\b[^\"']*\\1[^>]*>/i", $html, $match, PREG_OFFSET_CAPTURE)) {
            $tag = $match[0][0];
            $offset = $match[0][1] + strlen($tag);

            return substr($html, 0, $offset) . "\n" . $heading . substr($html, $offset);
        }

        // Conservative fallback for customized layouts without content-wrapper.
        if (preg_match("/<section\\b[^>]*class=([\"'])[^\"']*\\bcontent\\b[^\"']*\\1[^>]*>/i", $html, $match, PREG_OFFSET_CAPTURE)) {
            $offset = $match[0][1];

            return substr($html, 0, $offset) . $heading . "\n" . substr($html, $offset);
        }

        return $html;
    }

    /**
     * Browser-side fix for all current and module-added sidebar treeviews.
     *
     * Capture phase is intentional: it runs before the legacy jQuery/AdminLTE
     * bubbling handler, preventing two competing toggles from firing for the
     * same click. All changes are presentation-only CSS/DOM state changes.
     */
    private function sidebarFixMarkup(): string
    {
        return <<<'HTML'
<style data-s714-sidebar-fix>
/* S714: exact, no-animation sidebar state. Keep .active for the current page. */
.sidebar-menu li.s714-sidebar-collapsed > ul.treeview-menu,
.sidebar-menu li.s714-sidebar-collapsed > ul.sidebar-submenu,
.main-sidebar li.s714-sidebar-collapsed > ul.treeview-menu,
.main-sidebar li.s714-sidebar-collapsed > ul.sidebar-submenu {
    display: none !important;
}
.sidebar-menu li.s714-sidebar-open > ul.treeview-menu,
.sidebar-menu li.s714-sidebar-open > ul.sidebar-submenu,
.main-sidebar li.s714-sidebar-open > ul.treeview-menu,
.main-sidebar li.s714-sidebar-open > ul.sidebar-submenu {
    display: block !important;
}
.sidebar-menu ul.treeview-menu,
.sidebar-menu ul.sidebar-submenu,
.main-sidebar ul.treeview-menu,
.main-sidebar ul.sidebar-submenu {
    animation: none !important;
    transition: none !important;
}
</style>
<script data-s714-sidebar-fix>
(function () {
    'use strict';

    if (window.__s714SidebarFixInstalled) {
        return;
    }
    window.__s714SidebarFixInstalled = true;

    function directSubmenu(li) {
        if (!li || !li.children) {
            return null;
        }

        for (var i = 0; i < li.children.length; i++) {
            var child = li.children[i];
            if (child && child.tagName === 'UL' &&
                (child.classList.contains('treeview-menu') || child.classList.contains('sidebar-submenu'))) {
                return child;
            }
        }

        return null;
    }

    function setOpen(li, submenu, open) {
        if (!li || !submenu) {
            return;
        }

        // Stop an in-progress jQuery slideUp/slideDown immediately. jQuery's
        // stop(true, true) also clears queued animations that caused the
        // reported second-click flicker.
        if (window.jQuery) {
            try {
                window.jQuery(submenu).stop(true, true);
            } catch (ignore) {}
        }

        li.classList.toggle('s714-sidebar-open', open);
        li.classList.toggle('s714-sidebar-collapsed', !open);
        li.classList.toggle('menu-open', open);
        submenu.style.display = open ? 'block' : 'none';

        var toggle = null;
        for (var i = 0; i < li.children.length; i++) {
            if (li.children[i].tagName === 'A') {
                toggle = li.children[i];
                break;
            }
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }

        var anchor = target.closest('.main-sidebar a, .sidebar-menu a');
        if (!anchor) {
            return;
        }

        var li = anchor.parentElement;
        if (!li || li.tagName !== 'LI') {
            return;
        }

        // Only the direct parent anchor of a treeview is a toggle. Ordinary
        // page links inside the submenu continue navigating exactly as before.
        var submenu = directSubmenu(li);
        if (!submenu) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) {
            event.stopImmediatePropagation();
        }

        var isOpen = window.getComputedStyle(submenu).display !== 'none' &&
            !li.classList.contains('s714-sidebar-collapsed');

        if (isOpen) {
            setOpen(li, submenu, false);
        } else {
            // Independent module state: do not close another open module here.
            setOpen(li, submenu, true);
        }
    }, true);
})();
</script>
HTML;
    }
}
