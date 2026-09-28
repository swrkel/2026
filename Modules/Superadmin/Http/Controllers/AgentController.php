<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Agent;
use App\Country;
use App\Districts;
use App\Towns;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Superadmin\Entities\IncomeMethod;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\ReferralGroup;
use Modules\Superadmin\Entities\ReferralStartingCode;
use Yajra\DataTables\Facades\DataTables;

class AgentController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $agents = Agent::leftJoin('countries as c', 'superadmin.agents.country_id', '=', 'c.id')
                ->leftJoin('districts as d', 'superadmin.agents.district_id', '=', 'd.id')
                ->leftJoin('users as u', 'superadmin.agents.added_by', '=', 'u.id')
                ->select(
                    'superadmin.agents.*',
                    'c.country as country_name',
                    'd.name as district_name',
                    'u.username as added_by_name'
                );

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $agents->whereDate('superadmin.agents.date', '>=', request()->start_date);
                $agents->whereDate('superadmin.agents.date', '<=', request()->end_date);
            }

            if (!empty(request()->agent_name)) {
                $agents->where('superadmin.agents.name', 'like', '%' . request()->agent_name . '%');
            }

            if (!empty(request()->agent_code)) {
                $agents->where('superadmin.agents.agent_code', request()->agent_code);
            }

            if (!empty(request()->country_id)) {
                $agents->where('superadmin.agents.country_id', request()->country_id);
            }

            if (!empty(request()->city)) {
                $agents->where('superadmin.agents.city', request()->city);
            }

            if (!empty(request()->mobile_number)) {
                $agents->where('superadmin.agents.mobile_number', 'like', '%' . request()->mobile_number . '%');
            }

            if (!empty(request()->username)) {
                $agents->where('superadmin.agents.username', request()->username);
            }

            if (!empty(request()->nic_number)) {
                $agents->where('superadmin.agents.nic_number', request()->nic_number);
            }

            if (!empty(request()->referral_code)) {
                $agents->where('superadmin.agents.referral_code', request()->referral_code);
            }

            if (!empty(request()->added_by)) {
                $agents->where('superadmin.agents.added_by', request()->added_by);
            }

            return DataTables::of($agents)
                ->addColumn(
                    'action',
                    function ($row) {

                        $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li><a href="' . action('\Modules\Superadmin\Http\Controllers\AgentController@edit', [$row->id]) . '" class="edit_entity"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>
                        
                        <li><a data-href="' . action('\Modules\Superadmin\Http\Controllers\AgentController@destroy', [$row->id]) . '" class="delete_agent"><i class="glyphicon glyphicon-trash" style="color:brown; cursor: pointer;"></i> Delete</a></li>
                        ';

                        $html .=  '</ul></div>';
                        return $html;
                    }
                )
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->editColumn('mobile_number', function ($row) {
                    return $row->mobile_number;
                })
                ->addColumn('referral_group', '')
                ->addColumn('total_orders', '')
                ->addColumn('active_subscription', '')
                ->addColumn('income', '')
                ->addColumn('paid', '')
                ->addColumn('due', '')
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        $business_id = request()->session()->get('user.business_id');
        $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
        $date_filters['this_fy'] = $fy;
        $date_filters['this_month']['start'] = date('Y-m-01');
        $date_filters['this_month']['end'] = date('Y-m-t');
        $date_filters['this_week']['start'] = date('Y-m-d', strtotime('monday this week'));
        $date_filters['this_week']['end'] = date('Y-m-d', strtotime('sunday this week'));

        $agents = Agent::pluck('username', 'id');
        $packages = Package::pluck('name', 'id');
        $income_methods = IncomeMethod::pluck('income_method', 'id');

        $filter_dropdowns = Agent::getFilterDropdowns();
        extract($filter_dropdowns);

        return view('superadmin::agents.index')->with(compact(
            'date_filters',
            'agents',
            'packages',
            'income_methods',
            'countries',
            'agent_codes',
            'agent_names',
            'cities',
            'mobile_numbers',
            'usernames',
            'nic_numbers',
            'referral_codes',
            'added_bys'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $countries = Country::orderBy('country')->get();
        $districts = Districts::orderBy('name')->get();
        $cities = Towns::orderBy('name')->get();
        $agent_referral_group = ReferralGroup::where('group_name', 'Agent')->first();
        $default_referral_code = $this->generateNextReferralCode(
            $agent_referral_group ? $agent_referral_group->id : null
        );
        $agent_code = $this->generateNextAgentCode();
        $referral_codes = Agent::whereNotNull('referral_code')
            ->where('referral_code', '!=', '')
            ->groupBy('referral_code')
            ->pluck('referral_code');

        return view('superadmin::agents.create')->with(compact(
            'countries',
            'districts',
            'cities',
            'default_referral_code',
            'agent_code',
            'referral_codes'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $request->validate($this->agentValidationRules());

            $same_city_referral = Agent::where('city', $request->city)
                ->where('referral_code', $request->referral_code)
                ->exists();
            if ($same_city_referral) {
                $output = [
                    'success' => false,
                    'tab' => 'list_agent',
                    'msg' => __('superadmin::lang.city_referral_combination_exists')
                ];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json($output);
                }

                return redirect()->back()->withInput()->with('status', [
                    'success' => false,
                    'msg' => __('superadmin::lang.city_referral_combination_exists')
                ]);
            }

            DB::beginTransaction();

            $agent_code = $this->generateNextAgentCode();

            $plain_password = '';
            do {
                $plain_password = (string) random_int(10000, 99999);
            } while (Agent::where('passcode', $plain_password)->exists());

            $username = $request->username;
            if (empty($username)) {
                $username = $agent_code;
                $suffix = 1;
                while (Agent::where('username', $username)->exists()) {
                    $username = $agent_code . $suffix;
                    $suffix++;
                }
            }

            $data = $request->only([
                'name',
                'address',
                'country_id',
                'district_id',
                'city',
                'mobile_number',
                'mobile_no_2',
                'mobile_no_3',
                'land_number',
                'email',
                'nic_number',
                'referral_code',
                'bank_name',
                'account_number',
                'branch',
            ]);

            $data['date'] = now()->toDateString();
            $data['agent_code'] = $agent_code;
            $data['username'] = $username;
            $data['password'] = Hash::make($plain_password);
            $data['passcode'] = $plain_password;
            $data['added_by'] = Auth::id();
            $data['mobile_number'] = $this->normalizeMobileByCountry($data['mobile_number'], $request->country_id);
            $data['mobile_no_2'] = $this->normalizeMobileByCountry($request->mobile_no_2, $request->country_id);
            $data['mobile_no_3'] = $this->normalizeMobileByCountry($request->mobile_no_3, $request->country_id);

            if ($request->hasFile('nic_copy')) {
                $data['nic_copy'] = $this->businessUtil->uploadFile($request, 'nic_copy', 'agents', 'image');
            }
            if ($request->hasFile('agent_photo')) {
                $data['agent_photo'] = $this->businessUtil->uploadFile($request, 'agent_photo', 'agents', 'image');
            }

            Agent::create($data);
            DB::commit();

            $output = [
                'success' => true,
                'tab' => 'list_agent',
                'msg' => __('superadmin::lang.agent_created_with_credentials', [
                    'agent_code' => $agent_code,
                    'passcode' => $plain_password
                ])
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'list_agent',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('superadmin::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $agent = Agent::find($id);
        $countries = Country::orderBy('country')->pluck('country', 'id');
        $districts = Districts::orderBy('name')->pluck('name', 'id');
        $cities = Towns::orderBy('name')->pluck('name', 'name');

        return view('superadmin::agents.edit')->with(compact(
            'agent',
            'countries',
            'districts',
            'cities'
        ));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate($this->agentValidationRules($id));

            $same_city_referral = Agent::where('city', $request->city)
                ->where('referral_code', $request->referral_code)
                ->where('id', '!=', $id)
                ->exists();
            if ($same_city_referral) {
                return redirect()->back()->withInput()->with('status', [
                    'success' => false,
                    'msg' => __('superadmin::lang.city_referral_combination_exists')
                ]);
            }

            $input = $request->except('_token', '_method');
            $input['mobile_number'] = $this->normalizeMobileByCountry($request->mobile_number, $request->country_id);
            $input['mobile_no_2'] = $this->normalizeMobileByCountry($request->mobile_no_2, $request->country_id);
            $input['mobile_no_3'] = $this->normalizeMobileByCountry($request->mobile_no_3, $request->country_id);

            if ($request->hasFile('nic_copy')) {
                $input['nic_copy'] = $this->businessUtil->uploadFile($request, 'nic_copy', 'agents', 'image');
            }
            if ($request->hasFile('agent_photo')) {
                $input['agent_photo'] = $this->businessUtil->uploadFile($request, 'agent_photo', 'agents', 'image');
            }

            Agent::where('id', $id)->update($input);
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            Agent::where('id', $id)->delete();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    public function getDistrictsByCountry($country_id)
    {
        $districts = Districts::where('country_id', $country_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        return response()->json($districts);
    }

    public function getCitiesByDistrict($district_id)
    {
        $cities = Towns::where('district_id', $district_id)
            ->orderBy('name')
            ->pluck('name', 'name');

        return response()->json($cities);
    }

    public function storeDistrict(Request $request)
    {
        $request->validate([
            'country_id' => 'required|integer|exists:countries,id',
            'name' => 'required|string|max:255',
        ]);

        $exists = Districts::where('country_id', $request->country_id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->first();
        if ($exists) {
            return response()->json(['success' => true, 'id' => $exists->id, 'name' => $exists->name]);
        }

        $district = Districts::create([
            'business_id' => request()->session()->get('user.business_id') ?? 0,
            'date' => now()->toDateString(),
            'country_id' => $request->country_id,
            'name' => trim($request->name),
            'created_by' => Auth::id() ?? 0,
        ]);

        return response()->json(['success' => true, 'id' => $district->id, 'name' => $district->name]);
    }

    public function storeCity(Request $request)
    {
        $request->validate([
            'district_id' => 'required|integer|exists:districts,id',
            'name' => 'required|string|max:255',
        ]);

        $exists = Towns::where('district_id', $request->district_id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->first();
        if ($exists) {
            return response()->json(['success' => true, 'name' => $exists->name]);
        }

        $town = Towns::create([
            'business_id' => request()->session()->get('user.business_id') ?? 0,
            'date' => now()->toDateString(),
            'district_id' => $request->district_id,
            'name' => trim($request->name),
            'created_by' => Auth::id() ?? 0,
        ]);

        return response()->json(['success' => true, 'name' => $town->name]);
    }

    public function getNextReferralCode(Request $request)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $agent_group = ReferralGroup::where('group_name', 'Agent')->first();
        $code = $this->generateNextReferralCode($agent_group ? $agent_group->id : null);

        return response()->json([
            'success' => true,
            'code' => $code,
        ]);
    }

    protected function generateNextAgentCode()
    {
        $starting_code = 57;
        $agent_group = ReferralGroup::where('group_name', 'Agent')->first();
        if (!empty($agent_group)) {
            $starting = ReferralStartingCode::where('referral_group', $agent_group->id)->first();
            if (!empty($starting) && !empty($starting->starting_code)) {
                $starting_code = (int) $starting->starting_code;
            }
        }

        $max_numeric = Agent::whereNotNull('agent_code')->pluck('agent_code')->map(function ($code) {
            return (int) preg_replace('/\D+/', '', (string) $code);
        })->max();
        $next = max((int) $starting_code, (int) $max_numeric + 1);

        return 'AG-' . $next;
    }

    protected function generateNextReferralCode($referral_group_id = null)
    {
        $prefix = 'AG';
        $starting_code = 57;

        if (!empty($referral_group_id)) {
            $starting = ReferralStartingCode::where('referral_group', $referral_group_id)->first();
            if (!empty($starting)) {
                if (!empty($starting->prefix)) {
                    $prefix = $starting->prefix;
                }
                if (!empty($starting->starting_code)) {
                    $starting_code = (int) $starting->starting_code;
                }
            }
        }

        $max_numeric = Agent::whereNotNull('referral_code')->pluck('referral_code')->map(function ($code) {
            return (int) preg_replace('/\D+/', '', (string) $code);
        })->max();
        $next = max((int) $starting_code, (int) $max_numeric + 1);

        return $prefix . $next;
    }

    protected function agentValidationRules($id = null)
    {
        return [
            'name' => 'required|string|max:255',
            'country_id' => 'nullable|integer|exists:countries,id',
            'district_id' => 'nullable|integer|exists:districts,id',
            'city' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:30',
            'mobile_no_2' => 'nullable|string|max:30',
            'mobile_no_3' => 'nullable|string|max:30',
            'land_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'username' => $id ? 'required|string|max:255|unique:agents,username,' . $id : 'nullable|string|max:255|unique:agents,username',
            'nic_number' => $id ? 'required|string|max:255|unique:agents,nic_number,' . $id : 'required|string|max:255|unique:agents,nic_number',
            'referral_code' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'branch' => 'required|string|max:255',
            'nic_copy' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'agent_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    protected function getDialCodeByCountryCode($isoCode)
    {
        $isoCode = strtoupper(trim((string) $isoCode));
        $map = [
            'LK' => '94',
            'ID' => '62',
            'IN' => '91',
            'SG' => '65',
            'MY' => '60',
            'PH' => '63',
            'TH' => '66',
            'VN' => '84',
            'KH' => '855',
            'MM' => '95',
            'LA' => '856',
            'TL' => '670',
            'US' => '1',
            'CA' => '1',
            'GB' => '44',
            'AU' => '61',
            'NZ' => '64',
        ];

        return isset($map[$isoCode]) ? $map[$isoCode] : $isoCode;
    }

    protected function normalizeMobileByCountry($mobile, $country_id = null)
    {
        if (empty($mobile)) {
            return $mobile;
        }

        $mobile = trim((string) $mobile);
        if (strpos($mobile, '+') === 0) {
            return $mobile;
        }

        if (!empty($country_id)) {
            $country = Country::find($country_id);
            if (!empty($country) && !empty($country->country_code)) {
                $dialCode = $this->getDialCodeByCountryCode($country->country_code);
                return '+' . $dialCode . $mobile;
            }
        }

        return $mobile;
    }
}
