<?php

namespace Modules\Vat\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Vat\Entities\VatSetting;

use App\Account;
use App\AccountType;
use App\BusinessLocation;
// Separation step 3 (document 5-18): the shared `contacts` table is now
// reached through a VAT-owned model, so this file no longer depends on the
// core App\Contact class when the Contact module is retired for Customers.
// NOTE: SharedContact maps to `contacts`; the existing VatContact entity
// maps to `vat_contacts` and is a different data set.
use Modules\Vat\Entities\SharedContact as Contact;
use App\ContactGroup;
use App\User;
use Modules\Vat\Entities\VatPrefix;
use Modules\Vat\Entities\VatInvoice2Prefix;
use Modules\Superadmin\Entities\Subscription;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SettingsController extends Controller
{
    // Separation step 1 (document 5-18): number and date formatting now
    // comes from the module's own VatFormatter, a faithful transcription of
    // App\Utils\Util. Trait, not a constructor parameter, so the shared
    // controller signature is untouched.
    use \Modules\Vat\Support\FormatsVatNumbers;

    protected $transactionUtil;
    
    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }
    
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
       $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        abort_if($business_id <= 0, 403, 'Business context is unavailable.');
        
        if (request()->ajax()) {
            
           
            $expenses = VatSetting::leftjoin('users', 'users.id', '=', 'vat_settings.created_by')
                ->where('vat_settings.business_id', $business_id)

                ->select(
                    
                    'vat_settings.*',

                    'users.username'

                )->get();



            return Datatables::of($expenses)

                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->editColumn('effective_date', function ($row) {
                    $effectiveDate = $row->effective_date ?? null;

                    if (empty($effectiveDate) || $effectiveDate === '0000-00-00') {
                        return '';
                    }

                    try {
                        return $this->vatFormatter()->format_date($effectiveDate);
                    } catch (\Throwable $exception) {
                        try {
                            return Carbon::parse($effectiveDate)->format('d/m/Y');
                        } catch (\Throwable $ignored) {
                            return (string) $effectiveDate;
                        }
                    }
                })
                ->editColumn('vat_period', function($row){
                    if($row->is_custom_date == 0){
                        return ucfirst(str_replace("-"," ",$row->vat_period));
                    }else{
                        $dates = explode('_',$row->vat_period);
                        return $this->vatFormatter()->format_date($dates[0])." - ".$this->vatFormatter()->format_date($dates[1]);
                    }
                })
                ->editColumn('status', function ($row) {
                    if($row->status == 1){
                        $html = "<span class='badge bg-success'>".__('vat::lang.active')."</span>";
                    }else{
                        $html = "<span class='badge bg-danger'>".__('vat::lang.inactive')."</span>";
                    }
                    
                    return $html;
                })
                
                ->editColumn('tax_report_name', function ($row) {
                    return __('vat::lang.'.$row->tax_report_name);
                })
                

                ->rawColumns(['status'])

                ->make(true);

        }
        
        // VAT Add popups are rendered inline so the popup opens instantly without waiting for AJAX.
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        // S321 real fix: do not let NULL user flags hide valid users from the Add popup.
        $prefixes = VatPrefix::where('business_id', $business_id)->orderBy('prefix')->pluck('prefix', 'id');
        $prefixes2 = VatInvoice2Prefix::where('business_id', $business_id)->orderBy('prefix')->pluck('prefix', 'id');
        $users = User::where('business_id', $business_id)
            ->where(function ($query) { $query->where('is_cmmsn_agnt', 0)->orWhereNull('is_cmmsn_agnt'); })
            ->where(function ($query) { $query->where('is_customer', 0)->orWhereNull('is_customer'); })
            ->select('id', DB::raw("TRIM(CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), ' ', COALESCE(username, ''))) as full_name"))
            ->orderBy('first_name')
            ->pluck('full_name', 'id')
            ->map(function ($name, $id) { return trim($name) !== '' ? trim($name) : ('User #' . $id); });

        $accounts = collect();
        $asset_accounts = collect();
        $current_liabilities = AccountType::where('business_id', $business_id)->where('name', 'Current Liabilities')->first();
        if (!empty($current_liabilities)) {
            $accounts = Account::where('business_id', $business_id)->where('account_type_id', $current_liabilities->id)->pluck('name', 'id');
        }
        $current_assets = AccountType::where('business_id', $business_id)->where('name', 'Current Assets')->first();
        if (!empty($current_assets)) {
            $asset_accounts = Account::where('business_id', $business_id)->where('account_type_id', $current_assets->id)->pluck('name', 'id');
        }

        $customers = Contact::customersDropdown($business_id, false);
        $customer_group = ContactGroup::forDropdown($business_id);

        return view('vat::vat_settings.index')->with(compact(
            'business_id',
            'business_locations',
            'prefixes',
            'prefixes2',
            'users',
            'accounts',
            'asset_accounts',
            'customers',
            'customer_group'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        abort_if($business_id <= 0, 403, 'Business context is unavailable.');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        // S321 real fix: do not let NULL user flags hide valid users from the Add popup.
        $prefixes = VatPrefix::where('business_id', $business_id)->orderBy('prefix')->pluck('prefix', 'id');
        $prefixes2 = VatInvoice2Prefix::where('business_id', $business_id)->orderBy('prefix')->pluck('prefix', 'id');
        $users = User::where('business_id', $business_id)
            ->where(function ($query) { $query->where('is_cmmsn_agnt', 0)->orWhereNull('is_cmmsn_agnt'); })
            ->where(function ($query) { $query->where('is_customer', 0)->orWhereNull('is_customer'); })
            ->select('id', DB::raw("TRIM(CONCAT(COALESCE(surname, ''), ' ', COALESCE(first_name, ''), ' ', COALESCE(last_name, ''), ' ', COALESCE(username, ''))) as full_name"))
            ->orderBy('first_name')
            ->pluck('full_name', 'id')
            ->map(function ($name, $id) { return trim($name) !== '' ? trim($name) : ('User #' . $id); });

        return view('vat::vat_settings.settings_add')->with(compact('business_id', 'business_locations', 'prefixes', 'prefixes2', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $businessId = (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        abort_if($businessId <= 0, 403, 'Business context is unavailable.');

        $validated = $request->validate([
            'effective_date' => ['required', 'date'],
            'tax_report_name' => ['required', 'in:vat,tax'],
            'vat_period' => ['required', 'in:daily,weekly,monthly,bi-monthly,quarterly,bi-annually,annually,custom'],
            'report_cycle_starting_date' => ['required_if:vat_period,custom', 'nullable', 'date'],
            'report_cycle_ending_date' => ['required_if:vat_period,custom', 'nullable', 'date', 'after_or_equal:report_cycle_starting_date'],
        ]);

        $subscription = Subscription::active_subscription($businessId);
        $packageDetails = !empty($subscription) ? ($subscription->package_details ?? []) : [];

        if (!empty($packageDetails['vat_effective_date'])
            && strtotime($validated['effective_date']) < strtotime($packageDetails['vat_effective_date'])) {
            $output = [
                'success' => false,
                'msg' => __('vat::lang.minimum_effective_date')
                    . $this->vatFormatter()->format_date($packageDetails['vat_effective_date']),
            ];

            return $this->respond($request, $output, 422);
        }

        try {
            DB::transaction(function () use ($validated, $businessId) {
                VatSetting::where('business_id', $businessId)->update(['status' => 0]);

                $vatPeriod = $validated['vat_period'];
                $isCustomDate = 0;

                if ($validated['vat_period'] === 'custom') {
                    $isCustomDate = 1;
                    $vatPeriod = $validated['report_cycle_starting_date']
                        . '_' . $validated['report_cycle_ending_date'];
                }

                VatSetting::create([
                    'business_id' => $businessId,
                    'vat_period' => $vatPeriod,
                    'effective_date' => $validated['effective_date'],
                    'status' => 1,
                    'created_by' => auth()->id(),
                    'tax_report_name' => $validated['tax_report_name'],
                    'is_custom_date' => $isCustomDate,
                ]);
            });

            $output = [
                'success' => true,
                'msg' => __('messages.success'),
            ];
        } catch (\Throwable $exception) {
            Log::emergency(
                'File: ' . $exception->getFile()
                . ' Line: ' . $exception->getLine()
                . ' Message: ' . $exception->getMessage()
            );

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $this->respond($request, $output);
    }

    private function respond(Request $request, array $output, int $errorStatus = 200)
    {
        if ($request->ajax() || $request->expectsJson()) {
            $status = $output['success'] ? 200 : $errorStatus;

            return response()->json($output, $status);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
       
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }
}
