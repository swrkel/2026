<?php

namespace Modules\PetroGeneral\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Shows the saved / not saved message for every Petro General form.
 *
 * THE PROBLEM
 * Controllers in this module report the outcome of a save the usual way:
 *
 *     return redirect()->back()->with('status', ['success' => 1, 'msg' => '...']);
 *
 * PumpController::store() does it on both the success and the failure path, and
 * so does most of the module. Nothing ever rendered it. A search of every view
 * in this module found exactly one place reading session('status') -
 * petro_settings/index.blade.php - so on every other screen the message was
 * flashed and silently discarded. Add Pump saved the pump and told the user
 * nothing, which is what was reported, and the same silence applied to every
 * other Add / Edit / Update form in the module.
 *
 * WHY A MIDDLEWARE
 * Module views extend core's layouts.app, which is outside this module, so the
 * message cannot be rendered from a shared layout here. The alternatives were
 * editing 43 views one by one, or piggybacking on the sidebar partial - the only
 * partial core includes for us - which would mean emitting a <script> from
 * inside a <ul> menu.
 *
 * A middleware on the module's route groups covers every Petro General page from
 * one file, touches no existing view, and cannot collide with work being done on
 * the controllers.
 *
 * WHAT IT TOUCHES
 * Only ordinary, successful, HTML page responses. Redirects, JSON, downloads,
 * streamed responses and AJAX requests are all passed straight through
 * untouched. Forms that submit by AJAX already show their own toast from the
 * JSON they get back, so they neither need nor receive this.
 */
class RenderPetroGeneralStatusMessage
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        /*
         * KILL SWITCH
         *
         * Set PETROGENERAL_STATUS_TOAST=false in .env to turn this middleware off
         * without editing routes.php or redeploying. If a toast is still appearing
         * with this switched off, it is NOT coming from here and the producer is
         * elsewhere - that single test settles attribution.
         */
        if (! filter_var(env('PETROGENERAL_STATUS_TOAST', true), FILTER_VALIDATE_BOOLEAN)) {
            return $response;
        }

        if (! $this->shouldRender($request, $response)) {
            /*
             * The response is not a page this can write into - an AJAX reply, a
             * redirect, a download.
             *
             * For AJAX and JSON specifically the flash must be DROPPED here, not
             * left in the session. Several controllers in this module flash a
             * status and also answer the AJAX call with the same text in JSON;
             * the calling script toasts it, so the user has already seen it. If
             * the flash were left behind it would still be sitting in the
             * session on the next full page load and would pop up there instead
             * - on whatever screen the user happened to open next, with no
             * connection to what they were doing.
             *
             * That is what put a stale "Pump not found." on the Add Pump screen:
             * the message belonged to an earlier meter-sale action that answered
             * over AJAX, and it surfaced on the next page that could render it.
             *
             * A redirect is deliberately NOT cleared - its flash is meant for the
             * page it is about to land on.
             */
            if ($request->ajax() || $request->pjax() || $request->wantsJson()) {
                $request->session()->forget('status');
            }

            return $response;
        }

        $status = $request->session()->get('status');

        if (empty($status)) {
            return $response;
        }

        // Shown once. Clearing it here also stops a second render by any screen
        // that still reads the flash itself.
        $request->session()->forget('status');

        /*
         * Records WHICH request is carrying the message. One reproduction of the
         * stale "Pump not found." toast and this line names the page it was
         * rendered on and the page the user came from, which identifies the
         * action that set it. Remove once that is settled.
         */
        \Log::info('PetroGeneral status toast rendered', [
            'message'  => is_array($status) ? ($status['msg'] ?? null) : $status,
            'on_url'   => $request->fullUrl(),
            'referer'  => $request->headers->get('referer'),
            'user_id'  => optional(auth()->user())->id,
        ]);

        $message = is_array($status)
            ? ($status['msg'] ?? null)
            : (is_string($status) ? $status : null);

        if (empty($message)) {
            return $response;
        }

        // Controllers use 'success' => 1 / 0 / true / false inconsistently across
        // this module, so anything that is not an explicit falsey value counts as
        // a success. A plain string flash has no flag and is treated as success.
        $isSuccess = true;
        if (is_array($status) && array_key_exists('success', $status)) {
            $isSuccess = filter_var($status['success'], FILTER_VALIDATE_BOOLEAN);
        }

        $content = $response->getContent();

        if (! is_string($content) || stripos($content, '</body>') === false) {
            return $response;
        }

        // Guard against a double toast on any screen that already renders the
        // flash itself, by marking the page the first time this runs.
        if (str_contains($content, 'data-petrogeneral-status-rendered')) {
            return $response;
        }

        $script = $this->buildScript($message, $isSuccess);

        // Inject before the LAST closing body tag so the script lands after the
        // page's own scripts, by which point toastr is loaded.
        $position = strripos($content, '</body>');
        $content = substr($content, 0, $position) . $script . substr($content, $position);

        $response->setContent($content);

        return $response;
    }

    /**
     * Only ordinary HTML page responses are eligible.
     */
    private function shouldRender(Request $request, SymfonyResponse $response): bool
    {
        if ($request->ajax() || $request->pjax() || $request->wantsJson()) {
            return false;
        }

        // A redirect still holds the flash for the NEXT request - leave it alone
        // so the page it lands on is the one that shows the message.
        if ($response->isRedirection()) {
            return false;
        }

        if (! $response instanceof Response) {
            // Skips StreamedResponse, BinaryFileResponse and JsonResponse.
            return false;
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');

        return $contentType === '' || stripos($contentType, 'text/html') !== false;
    }

    private function buildScript(string $message, bool $isSuccess): string
    {
        $encodedMessage = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $method = $isSuccess ? 'success' : 'error';

        return <<<HTML

<script data-petrogeneral-status-rendered="1">
(function () {
    var petroGeneralStatusMessage = {$encodedMessage};

    function showPetroGeneralStatus() {
        if (typeof window.toastr === 'undefined') {
            return false;
        }
        window.toastr.{$method}(petroGeneralStatusMessage);
        return true;
    }

    if (!showPetroGeneralStatus()) {
        // toastr is loaded by the core layout; if this script somehow runs first,
        // wait briefly rather than losing the message entirely.
        var attempts = 0;
        var timer = setInterval(function () {
            attempts++;
            if (showPetroGeneralStatus() || attempts >= 40) {
                clearInterval(timer);
            }
        }, 50);
    }
})();
</script>
HTML;
    }
}
