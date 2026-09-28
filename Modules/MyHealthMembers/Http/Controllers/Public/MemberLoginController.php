<?php

namespace Modules\MyHealthMembers\Http\Controllers\Public;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthMemberLogin;

class MemberLoginController extends Controller
{
    protected function bootMyHealthViewNamespace(): void
    {
        $viewsPath = function_exists('module_path')
            ? module_path('MyHealthMembers', 'Resources/views')
            : base_path('Modules/MyHealthMembers/Resources/views');

        $langPath = function_exists('module_path')
            ? module_path('MyHealthMembers', 'Resources/lang')
            : base_path('Modules/MyHealthMembers/Resources/lang');

        if (is_dir($viewsPath)) {
            View::addNamespace('myhealthmembers', $viewsPath);
        }

        if (is_dir($langPath)) {
            Lang::addNamespace('myhealthmembers', $langPath);
        }
    }

    public function create()
    {
        $this->bootMyHealthViewNamespace();

        if (session('myhealth_member_id')) {
            return redirect()->route('myhealth.member.portal.dashboard');
        }

        return view('myhealthmembers::public.login');
    }

    public function login(Request $request)
    {
        $this->bootMyHealthViewNamespace();

        $request->validate([
            'passcode' => ['required', 'string', 'max:100'],
        ]);

        $passcode = trim((string) $request->input('passcode'));
        $login = $this->findLoginByPasscode($passcode);

        if (empty($login)) {
            $this->auditLogin(null, 'failed', 'Invalid passcode', $request);
            return back()->withErrors(['passcode' => 'Invalid My Health passcode.'])->withInput();
        }

        $member = MyHealthMember::find($login->member_id);

        if (empty($member) || (isset($member->is_active) && ! $member->is_active)) {
            $this->auditLogin($login->member_id ?? null, 'failed', 'Member inactive or not found', $request);
            return back()->withErrors(['passcode' => 'This My Health member account is not active.'])->withInput();
        }

        $this->clearMemberAuthSession();

        if ($this->otpRequired()) {
            $this->createOtpSession($member, $request);

            return redirect()->route('myhealth.public.login.otp.create')
                ->with('status', 'OTP sent to your registered email. SMS OTP will also be sent if enabled and wallet balance permits.');
        }

        $this->completeLogin($member, $login, $request);

        return redirect()->route('myhealth.member.portal.dashboard');
    }

    public function otpForm()
    {
        $this->bootMyHealthViewNamespace();

        if (! session('myhealth_member_otp_member_id')) {
            return redirect()->route('myhealth.public.login.create');
        }

        return view('myhealthmembers::public.otp');
    }

    public function verifyOtp(Request $request)
    {
        $this->bootMyHealthViewNamespace();

        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $memberId = session('myhealth_member_otp_member_id');
        $expectedOtp = session('myhealth_member_otp');
        $expiresAt = session('myhealth_member_otp_expires_at');
        $attempts = (int) session('myhealth_member_otp_attempts', 0);

        if (empty($memberId) || empty($expectedOtp) || empty($expiresAt)) {
            return redirect()->route('myhealth.public.login.create')
                ->withErrors(['passcode' => 'Your OTP session has expired. Please login again.']);
        }

        if (now()->greaterThan($expiresAt)) {
            $this->clearOtpSession();
            return redirect()->route('myhealth.public.login.create')
                ->withErrors(['passcode' => 'OTP expired. Please login again.']);
        }

        if ($attempts >= $this->otpMaxAttempts()) {
            $this->clearOtpSession();
            $this->auditLogin($memberId, 'failed', 'OTP max attempts exceeded', $request);
            return redirect()->route('myhealth.public.login.create')
                ->withErrors(['passcode' => 'Maximum OTP attempts exceeded. Please login again.']);
        }

        if (! hash_equals((string) $expectedOtp, (string) $request->input('otp'))) {
            session()->put('myhealth_member_otp_attempts', $attempts + 1);
            $this->auditLogin($memberId, 'failed', 'Invalid OTP', $request);
            return back()->withErrors(['otp' => 'Invalid OTP. Please try again.']);
        }

        $member = MyHealthMember::findOrFail($memberId);
        $login = MyHealthMemberLogin::where('member_id', $member->id)->first();

        $this->clearOtpSession();
        $this->completeLogin($member, $login, $request);

        return redirect()->route('myhealth.member.portal.dashboard');
    }

    public function resendOtp(Request $request)
    {
        $memberId = session('myhealth_member_otp_member_id');
        if (empty($memberId)) {
            return redirect()->route('myhealth.public.login.create');
        }

        $member = MyHealthMember::find($memberId);
        if (! $member) {
            $this->clearOtpSession();
            return redirect()->route('myhealth.public.login.create')
                ->withErrors(['passcode' => 'Your OTP session has expired. Please login again.']);
        }

        $this->createOtpSession($member, $request);
        return back()->with('status', 'A new OTP has been sent.');
    }

    public function logout(Request $request)
    {
        $memberId = session('myhealth_member_id');
        $this->clearMemberAuthSession();
        $request->session()->regenerateToken();
        $this->auditLogin($memberId, 'logout', 'Member logged out', $request);

        return redirect()->route('myhealth.public.login.create')->with('status', 'Logged out successfully.');
    }

    protected function findLoginByPasscode(string $passcode): ?MyHealthMemberLogin
    {
        $query = MyHealthMemberLogin::query();

        // Existing implementation stores the auto-generated passcode in login_code.
        $login = (clone $query)->where('login_code', $passcode)->first();
        if ($login) {
            return $login;
        }

        // Future-proof support for hashed password/passcode columns without breaking old records.
        $candidateLogins = MyHealthMemberLogin::whereNotNull('password')->limit(50)->get();
        foreach ($candidateLogins as $candidate) {
            try {
                if (Hash::check($passcode, (string) $candidate->password)) {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                // Ignore invalid hashes and continue searching by legacy passcode.
            }
        }

        return null;
    }

    protected function createOtpSession(MyHealthMember $member, Request $request): void
    {
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes($this->otpExpiryMinutes());

        session()->put('myhealth_member_otp', $otp);
        session()->put('myhealth_member_otp_member_id', $member->id);
        session()->put('myhealth_member_otp_expires_at', $expiresAt->toDateTimeString());
        session()->put('myhealth_member_otp_attempts', 0);

        $this->sendOtp($member, $otp);
        $this->auditLogin($member->id, 'otp_sent', 'OTP sent/queued for member login', $request);
    }

    protected function completeLogin(MyHealthMember $member, ?MyHealthMemberLogin $login, Request $request): void
    {
        if ($login) {
            $login->update(['last_login_at' => now()]);
        }

        session()->regenerate();
        session()->put('myhealth_member_id', $member->id);
        session()->put('myhealth_member_code', $member->myhealth_code ?? $member->member_code ?? '');
        session()->put('myhealth_member_name', $member->name ?? '');
        session()->put('myhealth_member_logged_in_at', now()->toDateTimeString());
        session()->put('myhealth_member_last_activity_at', now()->toDateTimeString());

        $this->notifyIdentityAccess($member, $request);
        $this->auditLogin($member->id, 'success', 'Member login successful', $request);
    }

    protected function clearMemberAuthSession(): void
    {
        session()->forget([
            'myhealth_member_id',
            'myhealth_member_code',
            'myhealth_member_name',
            'myhealth_member_logged_in_at',
            'myhealth_member_last_activity_at',
        ]);

        $this->clearOtpSession();
    }

    protected function clearOtpSession(): void
    {
        session()->forget([
            'myhealth_member_otp',
            'myhealth_member_otp_member_id',
            'myhealth_member_otp_expires_at',
            'myhealth_member_otp_attempts',
        ]);
    }

    protected function otpRequired(): bool
    {
        return $this->settingBool('member_login_otp_enabled', false)
            || $this->settingBool('enable_member_otp_login', false)
            || $this->settingBool('identityaccess_myhealth_otp_enabled', false);
    }

    protected function otpExpiryMinutes(): int
    {
        return max(1, (int) $this->settingValue('otp_expiry_minutes', 5));
    }

    protected function otpMaxAttempts(): int
    {
        return max(1, (int) $this->settingValue('otp_max_attempts', 3));
    }

    protected function settingBool(string $key, bool $default = false): bool
    {
        $value = $this->settingValue($key, $default ? '1' : '0');
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    protected function settingValue(string $key, $default = null)
    {
        try {
            if (Schema::hasTable('myhealth_settings')) {
                $row = DB::table('myhealth_settings')->where('key', $key)->first();
                if ($row) {
                    return $row->value ?? $default;
                }
            }
        } catch (\Throwable $e) {
            // Public login must remain available even if settings table is not ready.
        }

        return $default;
    }

    protected function sendOtp(MyHealthMember $member, string $otp): void
    {
        $message = 'Your My Health login OTP is ' . $otp . '. It expires in ' . $this->otpExpiryMinutes() . ' minutes.';

        // Email OTP is attempted first and is never wallet-blocked.
        if (! empty($member->email)) {
            try {
                Mail::raw($message, function ($mail) use ($member) {
                    $mail->to($member->email)->subject('My Health Login OTP');
                });
            } catch (\Throwable $e) {
                Log::warning('My Health member OTP email failed: ' . $e->getMessage());
            }
        }

        // Optional CommunicationHub integration. If available, it will decide provider,
        // wallet authorization and delivery rules. Failure is never allowed to block login.
        try {
            if (class_exists('Modules\\CommunicationHub\\Services\\CommunicationHubService')) {
                app('Modules\\CommunicationHub\\Services\\CommunicationHubService')->queueMessage([
                    'channel' => 'sms',
                    'recipient' => $member->mobile,
                    'subject' => 'My Health Login OTP',
                    'message' => $message,
                    'priority' => 'high',
                    'payload' => [
                        'module' => 'MyHealthMembers',
                        'purpose' => 'member_login_otp',
                        'member_id' => $member->id,
                        'member_code' => $member->myhealth_code ?? null,
                        'wallet_authorization' => 'communicationhub_digitalwallet_interface',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('My Health member OTP CommunicationHub queue failed: ' . $e->getMessage());
        }
    }

    protected function notifyIdentityAccess(MyHealthMember $member, Request $request): void
    {
        try {
            if (class_exists('Modules\\IdentityAccess\\Services\\Authentication\\IdentityAuthenticationService')) {
                app('Modules\\IdentityAccess\\Services\\Authentication\\IdentityAuthenticationService')
                    ->recordPortalLogin('MYHEALTH_MEMBER', (string) $member->id, [
                        'member_code' => $member->myhealth_code ?? null,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('My Health IdentityAccess login hook failed: ' . $e->getMessage());
        }
    }

    protected function auditLogin($memberId, string $action, string $notes, Request $request): void
    {
        try {
            if (! Schema::hasTable('myhealth_access_logs')) {
                return;
            }

            DB::table('myhealth_access_logs')->insert([
                'member_id' => $memberId ?: 0,
                'business_id' => (int) (session('business.id') ?? 0),
                'user_id' => null,
                'section' => 'member_portal_login',
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'accessed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('My Health member login audit failed: ' . $e->getMessage());
        }
    }
}
