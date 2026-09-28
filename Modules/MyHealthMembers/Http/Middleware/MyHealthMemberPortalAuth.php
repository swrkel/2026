<?php

namespace Modules\MyHealthMembers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MyHealthMemberPortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        $memberId = session('myhealth_member_id');

        if (empty($memberId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'My Health member login required.'], 401);
            }

            return redirect()->route('myhealth.public.login.create')
                ->withErrors(['passcode' => 'Please login to access your My Health member portal.']);
        }

        session()->put('myhealth_member_last_activity_at', now()->toDateTimeString());
        $this->auditAccess((int) $memberId, $request);

        return $next($request);
    }

    protected function auditAccess(int $memberId, Request $request): void
    {
        try {
            if (! Schema::hasTable('myhealth_access_logs')) {
                return;
            }

            // Keep portal access audit light. Dashboard/ajax refreshes should not flood logs.
            if ($request->isMethod('GET') && random_int(1, 10) !== 1) {
                return;
            }

            DB::table('myhealth_access_logs')->insert([
                'member_id' => $memberId,
                'business_id' => (int) (session('business.id') ?? 0),
                'user_id' => null,
                'section' => 'member_portal',
                'action' => $request->method() . ' ' . substr($request->path(), 0, 180),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'accessed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('My Health portal access audit failed: ' . $e->getMessage());
        }
    }
}
