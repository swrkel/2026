<?php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Litespeed\LSCache\LSCache;

final class TenantSessionLifecycle
{
    public static function logout(Request $request, string $guard = 'web', string $redirectPath = '/login')
    {
        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        try {
            LSCache::purge('*');
        } catch (\Throwable $e) {
        }

        $response = redirect()->to(self::hostUrl($request, $redirectPath));
        $cookie = (string) config('session.cookie');
        $response->withCookie(Cookie::forget($cookie, '/', null));

        $legacy = (string) env('SESSION_COOKIE', Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_session');
        if ($legacy !== '' && $legacy !== $cookie) {
            $response->withCookie(Cookie::forget($legacy, '/', null));
            $parts = explode('.', $request->getHost());
            if (count($parts) >= 2) {
                $response->withCookie(Cookie::forget($legacy, '/', '.' . implode('.', array_slice($parts, -2))));
            }
        }

        return $response;
    }

    public static function invalidate(Request $request): void
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public static function hostUrl(Request $request, string $path): string
    {
        return preg_match('#^https?://#i', $path)
            ? $path
            : rtrim($request->getSchemeAndHttpHost(), '/') . '/' . ltrim($path, '/');
    }
}
