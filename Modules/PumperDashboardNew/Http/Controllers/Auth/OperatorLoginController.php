<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\OperatorLoginRequest;
use Modules\PumperDashboardNew\Services\PoneLoginAttemptService;
use Modules\PumperDashboardNew\Services\PoneOperatorDirectoryService;
use Modules\PumperDashboardNew\Services\PoneOperatorSessionService;
use Modules\PumperDashboardNew\Services\PoneSchemaService;

class OperatorLoginController extends Controller
{
    public function __construct(
        private PoneSchemaService $schema,
        private PoneOperatorDirectoryService $directory,
        private PoneLoginAttemptService $attempts,
        private PoneOperatorSessionService $sessions
    ) {}

    public function show(Request $request)
    {
        if (auth()->check() && $request->session()->has('pone.operator_profile_id')) {
            return redirect()->route('pumper-dashboard-new.operator.dashboard');
        }

        $companyNumber = trim((string) $request->query('cc', $request->old('company_number')));
        if ($companyNumber === '') {
            return response()->view('pumperdashboardnew::errors.business', [
                'companyNumber' => '',
            ], 422);
        }

        $business = $this->directory->businessByCompanyNumber($companyNumber);
        if (! $business) {
            return response()->view('pumperdashboardnew::errors.business', [
                'companyNumber' => $companyNumber,
            ], 422);
        }

        return view('pumperdashboardnew::auth.login', [
            'business' => $business,
            'companyNumber' => $companyNumber,
            'schemaReady' => $this->schema->isInstalled(),
        ]);
    }

    public function login(OperatorLoginRequest $request)
    {
        $companyNumber = trim((string) $request->validated('company_number'));
        $passcode = (string) $request->validated('passcode');
        $ip = (string) $request->ip();

        $business = $this->directory->businessByCompanyNumber($companyNumber);
        if (! $business) {
            return back()->withInput()->withErrors([
                'company_number' => __('pumperdashboardnew::lang.business_not_found'),
            ]);
        }

        if ($this->attempts->isBlocked($companyNumber, $ip)) {
            return back()->withInput()->withErrors([
                'passcode' => __('pumperdashboardnew::lang.login_temporarily_blocked'),
            ]);
        }

        $user = $this->directory->findUserByPasscode((int) $business->id, $passcode);
        $operator = $user ? $this->directory->operatorForUser($user, (int) $business->id) : null;

        if (! $user || ! $operator) {
            $attempt = $this->attempts->fail((int) $business->id, $companyNumber, $ip, $passcode);

            return back()->withInput()->withErrors([
                'passcode' => __('pumperdashboardnew::lang.invalid_passcode_attempt', [
                    'count' => $attempt->attempt_count,
                    'max' => config('pumperdashboardnew.login.maximum_attempts', 5),
                ]),
            ]);
        }

        $profile = $this->directory->mapOperator($user, $operator, (int) $business->id);
        if (! $profile->login_enabled || $profile->status !== 'active') {
            return back()->withInput()->withErrors([
                'passcode' => __('pumperdashboardnew::lang.operator_access_disabled'),
            ]);
        }

        $this->attempts->reset((int) $business->id, $companyNumber, $ip);
        $this->sessions->start($request, $business, $user, $profile);
        $this->directory->touchLogin($profile, $ip);

        return redirect()->route('pumper-dashboard-new.operator.dashboard');
    }

    public function logout(Request $request)
    {
        $companyNumber = $request->session()->get('pone.company_number');
        $returnToMainLogin = $request->boolean('main_system');
        $this->sessions->end($request);

        if ($returnToMainLogin) {
            return redirect('/login' . ($companyNumber ? '?cc=' . urlencode((string) $companyNumber) : ''));
        }

        return redirect()->route('pumper-dashboard-new.login', ['cc' => $companyNumber]);
    }
}
