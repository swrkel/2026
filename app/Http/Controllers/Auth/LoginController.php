<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UsersOPTController;
use App\Services\TenantSessionLifecycle;
use App\Services\LoginBusinessSelector;
use App\Services\Authorization\SuperAdminImpersonation;
use App\User;
use App\UserSetting;
use App\Utils\BusinessUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $businessUtil;

    public function __construct(BusinessUtil $businessUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
    }


    public function showLoginForm()
    {
        return view('auth.login', [
            'businesses' => app(LoginBusinessSelector::class)->businesses(),
        ]);
    }

    public function username()
    {
        return 'username';
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

    protected function redirectTo()
    {
        $user = Auth::user();

        if ($user && $user->is_pump_operator) {
            return '/pumper-dashboard/pump-operators/dashboard';
        }

        if ($user && ! $user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        return '/home';
    }

    /**
     * Authenticate both AJAX and ordinary browser form submissions.
     *
     * The ordinary form response is an intentional fallback. Login must keep
     * working even when an optional page script, CDN, or browser extension
     * prevents the enhanced AJAX handler from loading.
     */
    public function login(Request $request)
    {
        // An ordinary credential login must never inherit an earlier
        // Login As Business bypass from the same browser session.
        if (class_exists(SuperAdminImpersonation::class)) {
            SuperAdminImpersonation::clear($request);
        }

        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $wantsJson = $request->ajax()
            || $request->expectsJson()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        try {
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

            /*
             * MA-002 (S-628): accept the username WITHOUT its business suffix.
             *
             * When the Superadmin setting enable_business_based_username is on,
             * Add User stores the name with the business id appended:
             *
             *     typed  Test23        stored  Test23-03
             *
             * The login screen never applied that suffix, so a user typing
             * exactly what they were given was told the details were incorrect.
             * The create log confirmed the row was perfect - password_ok true,
             * status active - and only the two username lines differed.
             *
             * If the plain name does not authenticate, this looks for a single
             * user whose name is that plus a "-NN" business suffix, and retries
             * with the stored name.
             *
             * THE PASSWORD IS STILL VERIFIED BY attempt() IN THE NORMAL WAY.
             * This only resolves WHICH username row to check, so it cannot let
             * anyone in who does not already know the password.
             *
             * Skipped when the typed name already ends in a suffix, and when
             * more than one row matches - an ambiguous match is never guessed.
             */
            if (! $authenticated) {
                $typedUsername = trim((string) $request->input('username'));

                if ($typedUsername !== '' && ! preg_match('/-\d{2,}$/', $typedUsername)) {
                    $suffixMatches = User::where('username', 'like', $typedUsername . '-%')
                        ->whereNull('deleted_at')
                        ->get()
                        ->filter(function ($candidate) use ($typedUsername) {
                            return (bool) preg_match(
                                '/^' . preg_quote($typedUsername, '/') . '-\d{2,}$/',
                                (string) $candidate->username
                            );
                        });

                    if ($suffixMatches->count() === 1) {
                        $resolvedUser = $suffixMatches->first();

                        $authenticated = Auth::guard('web')->attempt(
                            [
                                'username' => $resolvedUser->username,
                                'password' => $request->input('password'),
                            ],
                            $remember
                        );

                        if ($authenticated) {
                            $loginUser = $resolvedUser;
                        }
                    }
                }
            }


            // One-time compatibility for old installations that stored a
            // plaintext password. Upgrade it immediately after a valid match.
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
                return $this->loginFailureResponse(
                    $request,
                    $wantsJson,
                    'Login details incorrect. Please check and try again.'
                );
            }

            $request->session()->regenerate();
            $authenticatedUser = Auth::guard('web')->user();

            if (! $authenticatedUser) {
                throw new \RuntimeException('Authentication succeeded but the authenticated user was unavailable.');
            }

            $authenticatedUser->loadMissing('business', 'setting');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $authenticatedUser->unsetRelation('roles')->unsetRelation('permissions');

            if ($authenticatedUser->status !== 'active') {
                Auth::guard('web')->logout();

                return $this->loginFailureResponse(
                    $request,
                    $wantsJson,
                    __('lang_v1.user_inactive')
                );
            }

            if ($authenticatedUser->business && ! $authenticatedUser->business->is_active) {
                Auth::guard('web')->logout();

                return $this->loginFailureResponse(
                    $request,
                    $wantsJson,
                    __('lang_v1.business_inactive')
                );
            }

            $request->session()->put('lastActivityTime', time());

            $otpRequired = app(UsersOPTController::class)->sendOpt();
            if ($otpRequired) {
                if ($wantsJson) {
                    return response()->json([
                        'status' => true,
                        'step' => 'verify_step',
                    ]);
                }

                // Native-form fallback: render the same page directly so the
                // authenticated OTP modal can be displayed without passing
                // through the guest middleware again.
                return response()
                    ->view('auth.login', [
                        'force_login_verification' => true,
                        'businesses' => app(LoginBusinessSelector::class)->businesses(),
                    ]);
            }

            $redirect = $this->resolvedRedirect($authenticatedUser);

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
            $this->writeEmergencyLoginLog($request, $exception);

            return $this->loginFailureResponse(
                $request,
                $wantsJson,
                'Login could not be completed. The exact error has been recorded in storage/logs/login-emergency.log.',
                500
            );
        }
    }

    protected function resolvedRedirect($user): string
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

        return $this->redirectTo();
    }

    protected function loginFailureResponse(
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

    protected function writeEmergencyLoginLog(Request $request, \Throwable $exception): void
    {
        $context = [
            'time' => date('c'),
            'host' => (string) $request->getHost(),
            'username' => (string) $request->input('username'),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        Log::error('Login request failed.', $context);

        $logDirectory = storage_path('logs');
        if (! is_dir($logDirectory)) {
            @mkdir($logDirectory, 0775, true);
        }

        @file_put_contents(
            $logDirectory . '/login-emergency.log',
            json_encode($context, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function custom()
    {
        if (! Auth::check()) {
            return $this->showLoginForm();
        }

        return redirect('/home');
    }
}
