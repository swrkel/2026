<?php

namespace Modules\RiceMill\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\RiceMill\Services\DashboardAccessService;
use Modules\UserManagementNew\Services\UserPasscodeService;

class DashboardLoginController extends Controller
{
    public function __construct(
        private DashboardAccessService $access,
        private UserPasscodeService $passcodes
    ) {
    }

    public function show(Request $request)
    {
        $business = $this->resolveBusiness($request);
        abort_unless($this->access->moduleEnabled((int) $business->id), 403, 'Rice Mill Module is disabled for this business.');

        if (Auth::check()
            && (bool) $request->session()->get('rice_mill_dashboard_mode', false)
            && (int) $request->session()->get('rice_mill_dashboard_business_id') === (int) $business->id) {
            return redirect()->route('rice-mill-dashboard.dashboard');
        }

        return view('RiceMill::dashboard_portal.login', compact('business'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'business_id' => ['required', 'integer'],
            'company_number' => ['required', 'string', 'max:191'],
            'passcode' => ['required', 'string', 'max:191'],
        ]);

        $business = $this->resolveBusiness($request);
        abort_unless($this->access->moduleEnabled((int) $business->id), 403, 'Rice Mill Module is disabled for this business.');

        $user = $this->passcodes->findActiveUserByPasscode((int) $business->id, (string) $request->input('passcode'));
        if (! $user) {
            throw ValidationException::withMessages([
                'passcode' => 'The passcode is incorrect for this business.',
            ]);
        }

        Auth::guard('web')->loginUsingId($user->id);
        $request->session()->regenerate();
        $request->session()->put('rice_mill_dashboard_mode', true);
        $request->session()->put('rice_mill_dashboard_business_id', (int) $business->id);

        if (! $this->access->canEnter(Auth::user(), (int) $business->id)) {
            Auth::guard('web')->logout();
            $request->session()->forget(['rice_mill_dashboard_mode', 'rice_mill_dashboard_business_id']);
            throw ValidationException::withMessages([
                'passcode' => 'This user does not have Rice Mill Dashboard permission in User Management New.',
            ]);
        }


        return redirect()->route('rice-mill-dashboard.dashboard');
    }

    public function logout(Request $request)
    {
        $businessId = (int) ($request->session()->get('rice_mill_dashboard_business_id') ?: optional(Auth::user())->business_id);
        $business = $businessId > 0 ? $this->access->business($businessId) : null;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $query = $business
            ? '?cc=' . urlencode((string) $business->company_number) . '&business_id=' . urlencode((string) $business->id)
            : '';

        return redirect('/rice-mill-dashboard/login' . $query);
    }

    private function resolveBusiness(Request $request)
    {
        $businessId = (int) ($request->input('business_id') ?: $request->query('business_id', 0));
        $companyNumber = trim((string) ($request->input('company_number') ?: $request->query('cc', '')));

        abort_if($businessId <= 0 || $companyNumber === '', 403, 'Choose a valid Rice Mill business from the login page.');

        $business = $this->access->business($businessId, $companyNumber);
        abort_unless($business, 404, 'The selected Rice Mill business was not found in this tenant database.');

        return $business;
    }
}
