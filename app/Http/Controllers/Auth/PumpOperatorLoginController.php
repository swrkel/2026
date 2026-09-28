<?php

namespace App\Http\Controllers\Auth;

use App\Services\TenantSessionLifecycle;
use App\Services\GlobalReportsPagesFooter;
use App\Services\PumperLoginAttemptAuditService;

use App\Business;
use App\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\PumperDashboard\Entities\PumpOperatorAssignment;
use Illuminate\Support\Facades\Validator;
use Litespeed\LSCache\LSCache;
use App\PumperLoginAttempt;

class PumpOperatorLoginController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        ModuleUtil $moduleUtil
    ) {
        $this->moduleUtil = $moduleUtil;
    }


    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function login(Request $request)
    {
        $this->ensureTenantContextForHost($request);

        $settings = DB::table('site_settings')->where('id', 1)->select('*')->first();

        $cc = trim((string) $request->query('cc', ''));
        $business_id = (int) $request->query('business_id', 0);
        $login_display = $request->get('login_display');

        if ($cc === '' && $business_id <= 0) {
            abort(403, 'Unauthorized action.');
        }

        // Resolve the selected business from the ACTIVE TENANT DB. When both
        // values are supplied they must identify the same row. This prevents a
        // duplicate/stale company number from opening another business.
        $businessQuery = Business::with(['owner:id,surname,first_name,last_name'])
            ->select('id', 'name', 'owner_id', 'company_number');

        if ($business_id > 0) {
            $businessQuery->where('id', $business_id);
        }

        if ($cc !== '') {
            $businessQuery->where('company_number', $cc);
        }

        $business = $businessQuery->first();
        if (! $business) {
            abort(404, 'The selected business was not found in this tenant database.');
        }

        // Use the exact Super Admin setting: "Footer for the Reports & Pages".
        $reports_pages_footer = app(GlobalReportsPagesFooter::class)->text();

        if (Auth::user() && request()->session()->get('user.is_pump_operator')) {
            if ($login_display === 'my_auto_agent' || request()->session()->get('my_auto_agent_mode')) {
                return redirect()->to('/pumper-dashboard/pump-operators/my-auto-dashboard');
            }

            return redirect()->to('/pumper-dashboard/pump-operators/dashboard');
        }

        return view('pumperdashboard::login')->with(compact(
            'settings',
            'business',
            'cc',
            'business_id',
            'login_display',
            'reports_pages_footer'
        ));
    }

    public function postLogin(Request $request)
    {
        $this->ensureTenantContextForHost($request);

        $validator = Validator::make($request->all(), [
            'passcode' => 'required',
        ]);

        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg' => $validator->errors()->all()[0]
            ];

            return redirect()->back()->with('status', $output);
        }

        $selectedBusiness = $this->resolveBusinessForRequest($request);
        if (! $selectedBusiness) {
            return redirect()->back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'The selected business was not found. Please reopen the Pumper Dashboard login page.',
            ]);
        }

        $business_id = (int) $selectedBusiness->id;
        $companyNumber = trim((string) (
            optional($selectedBusiness)->company_number
            ?: $request->input('company_number')
            ?: $request->input('cc')
        ));
        $ip_address = trim((string) $request->ip()) ?: 'unknown';

        /*
         * Login attempts are business + IP specific. The old IP-only lookup
         * made one blocked row affect every business/operator using the same
         * public IP and also made the administration page appear to contain
         * only one operator.
         */
        $pumperLoginAttemptQuery = PumperLoginAttempt::query()
            ->where('ip_address', $ip_address);

        if ($business_id > 0) {
            $pumperLoginAttemptQuery->where(function ($query) use ($business_id, $companyNumber): void {
                $query->where('business_id', $business_id);

                if ($companyNumber !== '') {
                    $query->orWhere(function ($legacyQuery) use ($companyNumber): void {
                        $legacyQuery->whereNull('business_id')
                            ->where('company_number', $companyNumber);
                    });
                }
            });
        } elseif ($companyNumber !== '') {
            $pumperLoginAttemptQuery->where('company_number', $companyNumber);
        }

        $pumperLoginAttempt = $pumperLoginAttemptQuery
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", ['Blocked'])
            ->latest('id')
            ->first();

        if ($pumperLoginAttempt && $pumperLoginAttempt->status === 'Blocked') {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'Login attempts exceeded. Contact admin to unblock this Pumper Dashboard login.',
            ]);
        }

        $passcode = trim((string) $request->passcode);
        $userQuery = User::where('pump_operator_passcode', $passcode);
        if ($business_id > 0) {
            $userQuery->where('business_id', $business_id);
        }
        $user = $userQuery->first();

        if (is_null($user)) {
            if (is_null($pumperLoginAttempt)) {
                $pumperLoginAttempt = PumperLoginAttempt::create([
                    'business_id' => $business_id > 0 ? $business_id : null,
                    'company_number' => $companyNumber,
                    'ip_address' => $ip_address,
                    'last_entered_passcode' => $passcode,
                    'attempt_count' => 1,
                    'status' => 'Active',
                ]);
            } else {
                $wasBlocked = $pumperLoginAttempt->status === 'Blocked';
                $pumperLoginAttempt->business_id = $business_id > 0 ? $business_id : null;
                $pumperLoginAttempt->company_number = $companyNumber;
                $pumperLoginAttempt->attempt_count = (int) $pumperLoginAttempt->attempt_count + 1;
                $pumperLoginAttempt->status = $pumperLoginAttempt->attempt_count >= 5 ? 'Blocked' : 'Active';
                $pumperLoginAttempt->last_entered_passcode = $passcode;
                $pumperLoginAttempt->save();

                if (! $wasBlocked && $pumperLoginAttempt->status === 'Blocked') {
                    app(PumperLoginAttemptAuditService::class)->recordBlocked(
                        $pumperLoginAttempt,
                        $passcode,
                        'PumpOperatorLogin'
                    );
                }
            }
            $output = [
                'success' => 0,
                'msg' => "Your Passcode is incorrect. Please recheck and try again. {$pumperLoginAttempt->attempt_count} of 5 attempts",
            ];
            if ((! is_null($pumperLoginAttempt)) && $pumperLoginAttempt->attempt_count >= 5) {
                $output = [
                    'success' => 0,
                    'msg' => "Your passcode is incorrect. {$pumperLoginAttempt->attempt_count} of 5 attempts. Contact admin to reset your passcode.",
                ];
                return redirect()->back()->with('status', $output);
            }
            return redirect()->back()->with('status', $output);
        } elseif (! is_null($pumperLoginAttempt)) {
            $pumperLoginAttempt->business_id = $business_id > 0 ? $business_id : null;
            $pumperLoginAttempt->company_number = $companyNumber;
            $pumperLoginAttempt->attempt_count = 0;
            $pumperLoginAttempt->status = 'Active';
            $pumperLoginAttempt->save();
        }

        $pump_operator_id = $user->pump_operator_id;
        // $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->max('shift_number');
        $now = now();

// 1. Try to get the current open shift
$currentShift = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
    ->where('status', 'open')
    ->where(function ($q) use ($now) {
        $q->where('date_and_time', '<=', $now)   // current shift
          ->orWhere('date_and_time', '>', $now); // future shift
    })
    ->orderBy('date_and_time', 'asc')           // earliest valid shift
    ->first();

if (!$currentShift) {
    // 2. If no current open shift, get the first future shift
    $currentShift = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
        ->where('date_and_time', '>', $now) // future only
        ->orderBy('date_and_time', 'asc')   // next upcoming
        ->first();
}

if ($currentShift) {
    $shift_number = $currentShift->shift_number;
} else {
    $shift_number = null; // fallback if nothing found
}
        
        
        if (!empty($user)) {
            Auth::loginUsingId($user->id);

            $login_display = $request->get('login_display');
            if ($login_display === 'my_auto_agent') {
                $request->session()->put('my_auto_agent_mode', true);
                return redirect()->to('/pumper-dashboard/pump-operators/my-auto-dashboard');
            }

            $request->session()->forget('my_auto_agent_mode');

            return redirect()->to('/pumper-dashboard/pump-operators/dashboard?shift_number=' . urlencode($shift_number));
        } else {
            $output = [
                'success' => 0,
                'msg' => __('lang_v1.sorry_user_not_found')
            ];

            return redirect()->back()->with('status', $output);
        }

        return view('pumperdashboard::login')->with(compact(
            'business',
            'settings'
        ));
    }

    private function resolveBusinessForRequest(Request $request)
    {
        $business_id = (int) ($request->input('business_id') ?: $request->query('business_id', 0));
        $company_number = trim((string) (
            $request->input('company_number')
            ?: $request->input('cc')
            ?: $request->query('cc', '')
        ));

        $query = Business::query();

        if ($business_id > 0) {
            $query->where('id', $business_id);
        }

        if ($company_number !== '') {
            $query->where('company_number', $company_number);
        }

        if ($business_id <= 0 && $company_number === '') {
            return null;
        }

        return $query->first();
    }

    /**
     * Same route-collision guard as the main login controller. The legacy
     * /pump-operator/login route exists in both web.php and tenant.php, so the
     * controller must refuse to resolve a business from the central DB when a
     * registered tenant host is being used.
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
                \Log::warning('Pump operator login tenant host is not registered; central fallback blocked.', [
                    'host' => $host,
                ]);
                return;
            }

            $tenant = \App\Tenant::query()->find($domain->tenant_id);
            if (! $tenant) {
                return;
            }

            tenancy()->initialize($tenant);
        } catch (\Throwable $exception) {
            \Log::warning('Pump operator login tenant context fallback failed.', [
                'host' => $host,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * logout pump operator
     * @return Renderable
     */
    public function logout(Request $request)
    {
        $cc = $request->session()->get('business.company_number');
        $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');
        $from_admin = $request->session()->get('from_admin');
        $my_auto_agent_mode = $request->session()->get('my_auto_agent_mode');

        Auth::guard('web')->logout();
        TenantSessionLifecycle::invalidate($request);

        try {
            LSCache::purge('*');
        } catch (\Throwable $e) {
        }

        if (! empty($from_admin)) {
            Auth::guard('web')->loginUsingId($from_admin);
            $request->session()->regenerate();

            return redirect()->to(TenantSessionLifecycle::hostUrl($request, '/home'));
        }

        if ($request->main_system) {
            return redirect()->to(TenantSessionLifecycle::hostUrl(
                $request,
                '/login?cc=' . urlencode((string) $cc)
                    . (!empty($business_id) ? '&business_id=' . urlencode((string) $business_id) : '')
            ));
        }

        $loginUrl = '/pump-operator/login?cc=' . urlencode((string) $cc)
            . (!empty($business_id) ? '&business_id=' . urlencode((string) $business_id) : '');
        if ($my_auto_agent_mode) {
            $loginUrl .= '&login_display=my_auto_agent';
        }

        return redirect()->to(TenantSessionLifecycle::hostUrl($request, $loginUrl));
    }
}
