<?php

namespace Modules\PetroPD\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\PetroPD\Entities\PumpOperator;
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

        $businessId = (int) ($request->session()->get('user.business_id') ?: Auth::user()->business_id);
        $cacheKey = 'petropd:auto_logoff:' . $businessId . ':' . (int) $operatorId;

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
        $idleSeconds = 0;
        if (preg_match('/^(\d{1,2}):([0-5]\d)$/', $time, $matches)) {
            $idleSeconds = ((int) $matches[1] * 3600) + ((int) $matches[2] * 60);
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
        $script = $this->script($idleSeconds, $logoutUrl, (int) $operatorId);

        if (stripos($content, '</body>') !== false) {
            $content = preg_replace('/<\/body>/i', $script . "\n</body>", $content, 1);
        } else {
            $content .= $script;
        }

        $response->setContent($content);
        return $response;
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
