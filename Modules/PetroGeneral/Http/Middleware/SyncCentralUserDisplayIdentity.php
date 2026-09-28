<?php

namespace Modules\PetroGeneral\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keep the logged-in person's display identity stable when a request changes
 * from the central database to a tenant database.
 *
 * The same numeric user id can exist in both databases but belong to rows with
 * different display names. We deliberately copy only presentation fields from
 * the central users row. The runtime user's id, business_id, permissions,
 * pump-operator fields and every other tenant-owned attribute stay untouched.
 */
class SyncCentralUserDisplayIdentity
{
    private const SESSION_CACHE_KEY = '_central_user_display_identity_v1';

    public function handle(Request $request, Closure $next)
    {
        try {
            $this->sync($request);
        } catch (\Throwable $exception) {
            // This middleware must never block a working business page.
            Log::warning('PetroGeneral: unable to synchronize central user display identity.', [
                'message' => $exception->getMessage(),
                'path' => $request->path(),
            ]);
        }

        return $next($request);
    }

    private function sync(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $guard = auth()->guard();
        $guardSessionKey = method_exists($guard, 'getName') ? $guard->getName() : null;

        if (empty($guardSessionKey)) {
            return;
        }

        $loginUserId = (int) $request->session()->get($guardSessionKey, 0);
        if ($loginUserId <= 0) {
            return;
        }

        $identity = $this->centralIdentity($request, $loginUserId);
        if (empty($identity)) {
            return;
        }

        // Resolve whichever user row the current request is using, then replace
        // display fields only. Keeping business_id and permission-related fields
        // from the active request prevents a cross-business context leak.
        $runtimeUser = $guard->user();
        if ($runtimeUser && (int) $runtimeUser->getAuthIdentifier() === $loginUserId) {
            foreach ($this->displayFields() as $field) {
                if (array_key_exists($field, $identity) && $identity[$field] !== null) {
                    $runtimeUser->setAttribute($field, $identity[$field]);
                }
            }

            $guard->setUser($runtimeUser);
        }

        // layouts.app and several legacy pages read the user's display values
        // from the shared session instead of Auth::user(). Keep those values in
        // sync too, without touching user.business_id or business.id.
        foreach ($this->displayFields() as $field) {
            if (array_key_exists($field, $identity) && $identity[$field] !== null) {
                $request->session()->put('user.' . $field, $identity[$field]);
            }
        }
    }

    private function centralIdentity(Request $request, int $userId): array
    {
        $cached = $request->session()->get(self::SESSION_CACHE_KEY);
        if (
            is_array($cached)
            && (int) ($cached['id'] ?? 0) === $userId
            && ! empty($cached['identity'])
            && is_array($cached['identity'])
        ) {
            return $cached['identity'];
        }

        $row = null;
        $centralConnection = (string) config('tenancy.database.central_connection', '');

        // Stancl keeps the central connection name in tenancy.database. Use it
        // explicitly so an already-initialized tenant cannot silently turn this
        // lookup into another query against the tenant users table.
        if (
            $centralConnection !== ''
            && config('database.connections.' . $centralConnection) !== null
        ) {
            $row = DB::connection($centralConnection)
                ->table('users')
                ->where('id', $userId)
                ->first();
        } elseif (function_exists('tenancy') && method_exists(tenancy(), 'central')) {
            $row = tenancy()->central(function () use ($userId) {
                return DB::table('users')->where('id', $userId)->first();
            });
        } else {
            // Last-resort compatibility for installations without Stancl's
            // central helper. On non-tenant requests the default DB is central.
            $row = DB::table('users')->where('id', $userId)->first();
        }

        if (! $row) {
            return [];
        }

        $identity = [];
        foreach ($this->displayFields() as $field) {
            if (property_exists($row, $field)) {
                $identity[$field] = $row->{$field};
            }
        }

        if (empty($identity)) {
            return [];
        }

        $request->session()->put(self::SESSION_CACHE_KEY, [
            'id' => $userId,
            'identity' => $identity,
        ]);

        return $identity;
    }

    private function displayFields(): array
    {
        return ['first_name', 'last_name', 'surname', 'username', 'email'];
    }
}
