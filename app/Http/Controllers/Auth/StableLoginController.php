<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsersOPTController;
use App\Services\TenantSessionLifecycle;
use App\Services\Authorization\SuperAdminImpersonation;
use App\User;
use App\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * One canonical ERP login controller for both central and tenant hosts.
 *
 * Central and tenant route files register this controller explicitly. This
 * avoids Auth::routes() collisions and keeps the request on the current host,
 * database context, session cookie and CSRF token.
 */
class StableLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(Request $request)
    {
        $this->ensureTenantContextForHost($request);

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $this->ensureTenantContextForHost($request);

        // An ordinary credential login must never inherit an earlier
        // Login As Business bypass from the same browser session.
        if (class_exists(SuperAdminImpersonation::class)) {
            SuperAdminImpersonation::clear($request);
        }

        $wantsJson = $request->ajax()
            || $request->expectsJson()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        try {
            $request->validate([
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);

            $loginUser = User::with('setting')
                ->where('username', (string) $request->input('username'))
                ->first();

            if ($loginUser && $loginUser->setting && $loginUser->setting->re_captcha_enabled) {
                $request->validate([
                    'g-recaptcha-response' => ['required'],
                ]);
            }

            $credentials = $request->only('username', 'password');
            $remember = $request->boolean('remember');
            $authenticated = Auth::guard('web')->attempt($credentials, $remember);

            // Compatibility with old tenant databases that still contain a
            // plaintext password. Upgrade only after an exact valid match.
            if (! $authenticated && $loginUser && ! empty($loginUser->password)) {
                $hashInfo = password_get_info((string) $loginUser->password);
                $isLegacyPlaintext = ($hashInfo['algo'] ?? 0) === 0;

                if ($isLegacyPlaintext
                    && hash_equals((string) $loginUser->password, (string) $request->input('password'))) {
                    $loginUser->forceFill([
                        'password' => Hash::make((string) $request->input('password')),
                    ])->save();

                    $authenticated = Auth::guard('web')->attempt($credentials, $remember);
                }
            }

            if (! $authenticated) {
                /*
                 * MA-002 (S-628): record WHY a login was refused.
                 *
                 * This message is deliberately vague to the person at the
                 * screen - it must not reveal whether a username exists. But
                 * that also means we have had no way to tell a wrong password
                 * from a missing user, a soft-deleted row, or a request landing
                 * on the wrong tenant database.
                 *
                 * The user created as Test23-03 has password_ok true, status
                 * active and no duplicate in its creation log, yet the login is
                 * refused. One of the values below will say which.
                 *
                 * NO PASSWORD IS LOGGED - only whether a row was found and what
                 * state it is in. The message shown to the user is unchanged.
                 */
                $typedUsername = (string) $request->input('username');

                \Log::warning('MA-002 (S-628): login refused', [
                    'username_typed'   => $typedUsername,
                    'row_found'        => $loginUser ? true : false,
                    'row_id'           => $loginUser->id ?? null,
                    'row_status'       => $loginUser->status ?? null,
                    'row_business_id'  => $loginUser->business_id ?? null,
                    'row_deleted_at'   => $loginUser->deleted_at ?? null,
                    'hash_length'      => $loginUser ? strlen((string) $loginUser->password) : null,
                    'hash_matches'     => $loginUser
                        ? \Illuminate\Support\Facades\Hash::check(
                            (string) $request->input('password'),
                            (string) $loginUser->password
                        )
                        : null,
                    'db_in_use'        => \DB::connection()->getDatabaseName(),
                    'host'             => $request->getHost(),
                ]);

                return $this->failure(
                    $request,
                    $wantsJson,
                    'Incorrect Login. Please check the user name and password and try again.'
                );
            }

            $request->session()->regenerate();
            $user = Auth::guard('web')->user();

            if (! $user) {
                throw new \RuntimeException('Authentication succeeded but the user session was not available.');
            }

            $user->loadMissing('business', 'setting');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $user->unsetRelation('roles')->unsetRelation('permissions');

            if ((string) $user->status !== 'active') {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $this->failure($request, $wantsJson, __('lang_v1.user_inactive'));
            }

            if ($user->business && ! $user->business->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $this->failure($request, $wantsJson, __('lang_v1.business_inactive'));
            }

            $request->session()->put('lastActivityTime', time());

            $otpRequired = app(UsersOPTController::class)->sendOpt();
            if ($otpRequired) {
                $request->session()->save();

                if ($wantsJson) {
                    return response()->json([
                        'status' => true,
                        'step' => 'verify_step',
                    ]);
                }

                return response()->view('auth.login', [
                    'force_login_verification' => true,
                ]);
            }

            $redirect = $this->redirectFor($user);
            $request->session()->save();

            if ($wantsJson) {
                return response()->json([
                    'status' => true,
                    'redirect' => $redirect,
                    'step' => 'home_page',
                ]);
            }

            return redirect()->to($redirect);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Auth::guard('web')->logout();
            $this->writeEmergencyLog($request, $exception);

            return $this->failure(
                $request,
                $wantsJson,
                'Login could not be completed. Please check storage/logs/login-emergency.log.',
                500
            );
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::guard('web')->user();

        if ($user) {
            UserSetting::where('user_id', $user->id)->update([
                'verification_done' => false,
                'verification_attempt_count' => 0,
            ]);
        }

        return TenantSessionLifecycle::logout($request, 'web', '/login');
    }

    /**
     * Protect tenant login pages from route-order collisions.
     *
     * The application still has legacy /login routes in both web.php and
     * tenant.php. If the host-neutral route wins before Stancl middleware has
     * initialized tenancy, querying Business/User would otherwise use the
     * central database. Resolve the exact current host through the central
     * domains table and initialize only that registered tenant. Unknown hosts
     * are deliberately left unresolved; the login Blade then shows no central
     * business choices on a non-central host.
     */
    private function ensureTenantContextForHost(Request $request): void
    {
        if (! function_exists('tenancy') || tenancy()->initialized) {
            return;
        }

        $host = strtolower(trim((string) $request->getHost(), ". \t\n\r\0\x0B"));
        if ($host === '') {
            return;
        }

        $centralDomains = collect(config('tenancy.central_domains', []))
            ->map(static fn ($domain) => strtolower(trim((string) $domain, ". \t\n\r\0\x0B")))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (in_array($host, $centralDomains, true)) {
            return;
        }

        try {
            $domain = \Stancl\Tenancy\Database\Models\Domain::query()
                ->whereRaw('LOWER(domain) = ?', [$host])
                ->first();

            if (! $domain || empty($domain->tenant_id)) {
                Log::warning('Login tenant host is not registered; central fallback blocked.', [
                    'host' => $host,
                ]);
                return;
            }

            $tenant = \App\Tenant::query()->find($domain->tenant_id);
            if (! $tenant) {
                Log::warning('Login tenant domain has no matching tenant row.', [
                    'host' => $host,
                    'tenant_id' => $domain->tenant_id,
                ]);
                return;
            }

            tenancy()->initialize($tenant);
        } catch (\Throwable $exception) {
            Log::warning('Login tenant context fallback failed.', [
                'host' => $host,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function redirectFor($user): string
    {
        $previousUrl = Session::get('previousUrl');

        if ($previousUrl
            && Route::has('shipping.index')
            && strpos((string) $previousUrl, (string) route('shipping.index')) !== false) {
            return (string) $previousUrl;
        }

        if ((int) $user->member === 1) {
            return '/member/home';
        }

        if ($user->is_pump_operator) {
            return '/pumper-dashboard/pump-operators/dashboard';
        }

        if (! $user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        return '/home';
    }

    private function failure(
        Request $request,
        bool $wantsJson,
        string $message,
        int $status = 200
    ) {
        if ($wantsJson) {
            return response()->json([
                'status' => false,
                'msg' => $message,
            ], $status);
        }

        return redirect()
            ->back()
            ->withInput($request->only('username'))
            ->withErrors(['username' => $message]);
    }

    private function writeEmergencyLog(Request $request, \Throwable $exception): void
    {
        $context = [
            'time' => date('c'),
            'host' => (string) $request->getHost(),
            'path' => (string) $request->path(),
            'username' => (string) $request->input('username'),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        Log::error('Stable login request failed.', $context);

        $directory = storage_path('logs');
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        @file_put_contents(
            $directory.'/login-emergency.log',
            json_encode($context, JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
