<?php

namespace App\Services\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Owns the complete lifecycle of "Login As Business" authorization.
 *
 * The old implementation trusted two loose session flags. Any request carrying
 * those flags received every Gate permission, even when the authenticated user
 * no longer matched the business account that the Super Admin selected. This
 * service binds the bypass to the original user, impersonated user, business,
 * and current Laravel session by means of an HMAC signature.
 */
class SuperAdminImpersonation
{
    public const LOGGED_IN_KEY = 'superadmin-logged-in';
    public const ORIGINAL_USER_ID_KEY = 'superadmin-user-id';
    public const TARGET_USER_ID_KEY = 'superadmin-impersonated-user-id';
    public const TARGET_BUSINESS_ID_KEY = 'superadmin-impersonation-business-id';
    public const SIGNATURE_KEY = 'superadmin-impersonation-signature';

    /** @return array<int, string> */
    public static function sessionKeys(): array
    {
        return [
            self::LOGGED_IN_KEY,
            self::ORIGINAL_USER_ID_KEY,
            self::TARGET_USER_ID_KEY,
            self::TARGET_BUSINESS_ID_KEY,
            self::SIGNATURE_KEY,
            // Historical aliases are also removed so an older patch can never
            // re-enable a permission bypass after an ordinary login.
            'superadmin_logged_in',
            'superadmin_user_id',
            'superadmin_impersonated_user_id',
            'superadmin_impersonation_business_id',
            'superadmin_impersonation_signature',
        ];
    }

    public static function begin(
        Request $request,
        int $originalUserId,
        Authenticatable $targetUser,
        int $businessId
    ): void {
        $targetUserId = (int) $targetUser->getAuthIdentifier();

        if ($originalUserId <= 0 || $targetUserId <= 0 || $businessId <= 0 || $originalUserId === $targetUserId) {
            throw new \InvalidArgumentException('Invalid Super Admin impersonation context.');
        }

        // Rotate the session identifier when the authenticated identity changes.
        // Existing business/session data is preserved by Laravel's regenerate().
        $request->session()->regenerate();

        $request->session()->put([
            self::LOGGED_IN_KEY => 1,
            self::ORIGINAL_USER_ID_KEY => $originalUserId,
            self::TARGET_USER_ID_KEY => $targetUserId,
            self::TARGET_BUSINESS_ID_KEY => $businessId,
        ]);

        $request->session()->put(
            self::SIGNATURE_KEY,
            self::signature(
                (string) $request->session()->getId(),
                $originalUserId,
                $targetUserId,
                $businessId
            )
        );
    }

    public static function clear(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->forget(self::sessionKeys());
    }

    public static function originalUserId(?Request $request = null): int
    {
        try {
            $request = $request ?: request();
            if (! $request->hasSession()) {
                return 0;
            }

            return (int) $request->session()->get(self::ORIGINAL_USER_ID_KEY, 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function isActive(
        ?Authenticatable $user = null,
        ?Request $request = null
    ): bool {
        try {
            $request = $request ?: request();
            if (! $request->hasSession()) {
                return false;
            }

            $user = $user ?: Auth::guard('web')->user();
            if (! $user) {
                return false;
            }

            $session = $request->session();
            $loggedIn = (int) $session->get(self::LOGGED_IN_KEY, 0);
            $originalUserId = (int) $session->get(self::ORIGINAL_USER_ID_KEY, 0);
            $targetUserId = (int) $session->get(self::TARGET_USER_ID_KEY, 0);
            $targetBusinessId = (int) $session->get(self::TARGET_BUSINESS_ID_KEY, 0);
            $storedSignature = (string) $session->get(self::SIGNATURE_KEY, '');

            $authenticatedUserId = (int) $user->getAuthIdentifier();
            $authenticatedBusinessId = (int) ($user->business_id ?? 0);
            $sessionUserId = (int) $session->get('user.id', 0);
            $sessionBusinessId = (int) $session->get('user.business_id', 0);

            if ($loggedIn !== 1
                || $originalUserId <= 0
                || $targetUserId <= 0
                || $targetBusinessId <= 0
                || $storedSignature === ''
                || $originalUserId === $targetUserId
                || $authenticatedUserId !== $targetUserId
                || $authenticatedBusinessId !== $targetBusinessId
                || $sessionUserId !== $targetUserId
                || $sessionBusinessId !== $targetBusinessId) {
                return false;
            }

            $expectedSignature = self::signature(
                (string) $session->getId(),
                $originalUserId,
                $targetUserId,
                $targetBusinessId
            );

            return hash_equals($expectedSignature, $storedSignature);
        } catch (\Throwable $e) {
            // Authorization bypasses must always fail closed.
            return false;
        }
    }

    private static function signature(
        string $sessionId,
        int $originalUserId,
        int $targetUserId,
        int $businessId
    ): string {
        $payload = implode('|', [
            $sessionId,
            $originalUserId,
            $targetUserId,
            $businessId,
        ]);

        return hash_hmac('sha256', $payload, self::signingKey());
    }

    private static function signingKey(): string
    {
        $configuredKey = (string) config('app.key', '');

        if (str_starts_with($configuredKey, 'base64:')) {
            $decoded = base64_decode(substr($configuredKey, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        if ($configuredKey === '') {
            throw new \RuntimeException('APP_KEY is required for Super Admin impersonation.');
        }

        return $configuredKey;
    }
}
