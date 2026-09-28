<?php

namespace Modules\Vat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * MA-002 CLICK DIAGNOSTIC - TEMPORARY.
 *
 * Logs whatever the page reports. Deliberately has NO validation rules and NO
 * field whitelist:
 *
 *   - v1 validated field lengths. An over-long field returned 422 and logged
 *     NOTHING, which looked identical to "not deployed".
 *   - v2 used a fixed field list, so any new field added client-side was
 *     silently dropped.
 *
 * A diagnostic must never fail quietly. Everything except the CSRF token is
 * accepted, cast to string and truncated here.
 *
 * DELETE this controller, its route and the ma002_click_diagnostic partial
 * once the cause is known.
 */
class Ma002DiagnosticController extends Controller
{
    public function clickReport(Request $request)
    {
        $payload = [];

        foreach ($request->except(['_token']) as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $payload[mb_substr((string) $key, 0, 40)] = mb_substr((string) $value, 0, 900);
        }

        $payload['user_id'] = optional($request->user())->id;

        Log::warning('MA-002 CLICK DIAGNOSTIC', $payload);

        return response()->noContent();
    }
}
