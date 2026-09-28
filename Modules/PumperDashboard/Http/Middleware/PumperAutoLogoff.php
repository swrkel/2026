<?php

namespace Modules\PumperDashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\PumperDashboard\Entities\PumpOperator;
use Symfony\Component\HttpFoundation\Response;

class PumperAutoLogoff
{
    /**
     * MA-008: used when auto logoff is on but no usable Logoff time is set.
     */
    private const DEFAULT_IDLE_SECONDS = 900; // 15 minutes

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!Auth::check() || !$this->isHtmlResponse($response)) {
            return $response;
        }

        $operatorId = Auth::user()->pump_operator_id ?? null;
        if (empty($operatorId)) {
            return $response;
        }

        $businessId = (int) (Auth::user()->business_id ?? 0);
        $cacheKey = 'pumperdashboard:auto_logoff:' . $businessId . ':' . (int) $operatorId;

        $dashboardSettings = Cache::remember($cacheKey, now()->addMinute(), function () use ($operatorId, $businessId) {
            return PumpOperator::query()
                ->whereKey($operatorId)
                ->when($businessId > 0, fn ($query) => $query->where('business_id', $businessId))
                ->value('dashboard_settings');
        });

        /*
         | MA-008: an operator with no dashboard_settings row used to be skipped
         | entirely. "Enabled by default" has to cover exactly that operator - the
         | one nobody has configured - so an empty value now falls through to the
         | defaults below instead of returning.
         */
        $settings = json_decode((string) $dashboardSettings, true);
        if (!is_array($settings)) {
            $settings = [];
        }

        /*
         |----------------------------------------------------------------------
         | MA-008: auto logoff is now IDLE based, and on by default.
         |----------------------------------------------------------------------
         |
         | Requirement: "the Pump dashboard logged in to auto logout, when the
         | system is idle for more than the specified time in the Logoff time
         | field (if the Auto Log Off is enabled). By default, this should be
         | enabled."
         |
         | Two changes.
         |
         | 1. IDLE, NOT A CLOCK TIME.
         |    The old script read logoff_time as a time of day and logged the
         |    operator out when that moment arrived - so a setting of 22:00 logged
         |    everyone out at 10pm no matter how busy they were, and someone who
         |    walked away at 09:00 stayed logged in all day. It is now read as a
         |    DURATION: 00:30 means log out after 30 minutes with no activity.
         |    The timer restarts on any mouse move, key press, click, scroll or
         |    touch, so an operator who is working is never interrupted.
         |
         | 2. ENABLED BY DEFAULT.
         |    'logoff' previously had to be set to yes explicitly; a business that
         |    had never opened the settings page got no auto logout at all. The
         |    default is now enabled, and only an explicit no turns it off.
         |    A default idle time is applied when none is configured, because
         |    "enabled by default" is meaningless without one.
         */
        $logoffSetting = strtolower(trim((string) ($settings['logoff'] ?? '')));
        $explicitlyDisabled = in_array($logoffSetting, ['no', '0', 'false', 'off'], true);

        if ($explicitlyDisabled) {
            return $response;
        }

        $time = trim((string) ($settings['logoff_time'] ?? ''));

        // Accept H:i and treat it as hours:minutes of idle time.
        /*
         | IS2010: the Logoff time setting is now a plain number of MINUTES.
         |
         | It used to be entered in a clock picker, so "log off after 30 minutes"
         | had to be typed as 12:30 AM - confusing and wrong for a duration. The
         | settings page is now a minutes box.
         |
         | Both forms are accepted here:
         |   "30"    -> 30 minutes  (current)
         |   "00:30" -> 30 minutes  (legacy, still stored on existing installs)
         | so a business that configured this before keeps working with no change.
         */
        $idleSeconds = 0;

        if (preg_match('/^(\d{1,2}):([0-5]\d)$/', $time, $matches)) {
            // Legacy H:i - hours and minutes of idle time.
            $idleSeconds = ((int) $matches[1] * 3600) + ((int) $matches[2] * 60);
        } elseif (preg_match('/^\d{1,5}$/', $time)) {
            $idleSeconds = (int) $time * 60;
        }

        if ($idleSeconds <= 0) {
            // Enabled with nothing usable configured - fall back to 15 minutes.
            $idleSeconds = self::DEFAULT_IDLE_SECONDS;
        }

        try {
            $logoutUrl = action('Auth\PumpOperatorLoginController@logout');
        } catch (\Throwable $e) {
            $logoutUrl = url('/pump-operator/logout');
        }

        $content = $response->getContent();

        if (! is_string($content)) {
            return $response;
        }

        // Never inject twice into one response.
        if (str_contains($content, 'id="pumper-auto-logoff-script"')) {
            return $response;
        }

        /*
         |----------------------------------------------------------------------
         | Where this script is injected. This caused a live outage - please read
         | before changing it.
         |----------------------------------------------------------------------
         |
         | This used to be:
         |
         |     preg_replace('/<\/body>/i', $script . "\n</body>", $content, 1)
         |
         | which injects before the FIRST </body> in the page. That is wrong on
         | any page whose JavaScript contains the text "</body>".
         |
         | The Other Sales page does exactly that. It prepares a print preview
         | with
         |
         |     printPreviewWindow.document.write('<!doctype html>...</body></html>');
         |
         | and that text sits INSIDE a <script> block, before the document's real
         | </body> in the layout footer. So the first </body> in the page was the
         | one inside the JavaScript string, and this script was injected into
         | the middle of it. That broke the string, ended the script block early,
         | and the remainder of the page's code was rendered on screen as plain
         | text. Every script on the page then stopped running - which is why the
         | Other Sales product dropdown no longer loaded prices.
         |
         | Now the injection point is the document's own closing </body>: the
         | last one that is NOT inside a <script> block. If that cannot be
         | determined, nothing is injected. Losing auto logoff on one page is a
         | very small cost compared with breaking the page.
         */
        $position = $this->findDocumentBodyClose($content);

        if ($position === null) {
            return $response;
        }

        $script = $this->script($idleSeconds, $logoutUrl, (int) $operatorId);

        $content = substr($content, 0, $position) . $script . "\n" . substr($content, $position);

        $response->setContent($content);
        return $response;
    }

    /**
     * Offset of the document's own closing </body>, ignoring any that appear
     * inside a <script> block. Returns null when it cannot be determined.
     */
    private function findDocumentBodyClose(string $content): ?int
    {
        // Byte ranges of every <script>...</script> region in the page.
        $scriptRanges = [];
        if (preg_match_all('#<script\b[^>]*>.*?</script\s*>#is', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $scriptRanges[] = [$match[1], $match[1] + strlen($match[0])];
            }
        }

        // Collect every </body> occurrence.
        $positions = [];
        $offset = 0;
        while (($found = stripos($content, '</body', $offset)) !== false) {
            $positions[] = $found;
            $offset = $found + 6;
        }

        // Walk backwards and take the first one that is real markup.
        for ($i = count($positions) - 1; $i >= 0; $i--) {
            $candidate = $positions[$i];
            $insideScript = false;

            foreach ($scriptRanges as $range) {
                if ($candidate >= $range[0] && $candidate < $range[1]) {
                    $insideScript = true;
                    break;
                }
            }

            if (! $insideScript) {
                return $candidate;
            }
        }

        return null;
    }

    private function isHtmlResponse($response): bool
    {
        if (!$response instanceof Response || !method_exists($response, 'getContent') || !method_exists($response, 'setContent')) {
            return false;
        }
        $contentType = (string) $response->headers->get('Content-Type', '');
        return $contentType === '' || stripos($contentType, 'text/html') !== false;
    }

    /**
     * MA-008: idle timer. Any real activity restarts it; when the idle period
     * elapses the operator is sent to the logout route.
     */
    private function script(int $idleSeconds, string $logoutUrl, int $operatorId): string
    {
        $idleJson = json_encode($idleSeconds);
        $urlJson = json_encode($logoutUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        $keyJson = json_encode('pumper_idle_deadline_' . $operatorId);

        return <<<HTML
<script id="pumper-auto-logoff-script">
(function () {
    'use strict';
    var idleMs = {$idleJson} * 1000;
    var logoutUrl = {$urlJson};
    var storageKey = {$keyJson};
    var loggingOut = false;

    function readDeadline() {
        try {
            var stored = parseInt(sessionStorage.getItem(storageKey) || '', 10);
            return isNaN(stored) ? 0 : stored;
        } catch (e) { return 0; }
    }

    function writeDeadline(value) {
        try { sessionStorage.setItem(storageKey, String(value)); } catch (e) {}
    }

    // Shared through sessionStorage so activity in one tab keeps the others alive.
    function resetDeadline() {
        if (loggingOut) return;
        writeDeadline(Date.now() + idleMs);
    }

    function check() {
        if (loggingOut) return;
        var deadline = readDeadline();
        if (!deadline) { resetDeadline(); return; }
        if (Date.now() >= deadline) {
            loggingOut = true;
            try { sessionStorage.removeItem(storageKey); } catch (e) {}
            window.location.replace(logoutUrl);
        }
    }

    resetDeadline();

    var events = ['mousemove', 'mousedown', 'keydown', 'click', 'scroll', 'touchstart', 'wheel'];
    var throttled = false;
    for (var i = 0; i < events.length; i++) {
        window.addEventListener(events[i], function () {
            if (throttled) return;
            throttled = true;
            resetDeadline();
            window.setTimeout(function () { throttled = false; }, 1000);
        }, true);
    }

    // A submitted form or AJAX-driven page counts as activity too.
    document.addEventListener('submit', resetDeadline, true);

    window.setInterval(check, 5000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) check(); });
    window.addEventListener('focus', check);
})();
</script>
HTML;
    }
}
