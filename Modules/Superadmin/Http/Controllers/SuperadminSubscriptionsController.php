<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Entities\Package;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Utils\BusinessUtil;
use App\Business;
use App\System;

class SuperadminSubscriptionsController extends BaseController
{
    protected $businessUtil;

    /**
     * Constructor
     *
     * @param BusinessUtil $businessUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $superadmin_subscription = collect($this->getSubscriptionRows())
                ->sortByDesc(function ($row) {
                    return strtotime((string) ($row['created_at'] ?? '1970-01-01 00:00:00'));
                })
                ->values();

            return DataTables::of($superadmin_subscription)
                ->addColumn(
                    'action',
                    function ($row) {
                        if (empty(data_get($row, 'id'))) {
                            return '<span class="text-muted">No subscription</span>';
                        }

                        $statusQuery = [
                            'superadmin_subscription' => data_get($row, 'id'),
                            'tenant_id' => data_get($row, 'tenant_id'),
                            'tenant_database' => data_get($row, 'tenant_database'),
                        ];
                        $editQuery = [
                            'id' => data_get($row, 'id'),
                            'tenant_id' => data_get($row, 'tenant_id'),
                            'tenant_database' => data_get($row, 'tenant_database'),
                        ];

                        $statusUrl = url('/superadmin/superadmin-subscription/' . rawurlencode((string) data_get($row, 'id')) . '/edit') . '?' . http_build_query($statusQuery);
                        $editUrl = url('/superadmin/edit-subscription/' . rawurlencode((string) data_get($row, 'id'))) . '?' . http_build_query($editQuery);

                        return '<div class="sa-subscription-actions">'
                            . '<button type="button" data-href="' . e($statusUrl) . '" class="btn btn-info btn-xs change_status" title="' . e(__('superadmin::lang.status')) . '">'
                            . '<i class="fa fa-toggle-on" aria-hidden="true"></i> ' . e(__('superadmin::lang.status'))
                            . '</button>'
                            . '<button type="button" data-href="' . e($editUrl) . '" class="btn btn-primary btn-xs btn-modal" data-container=".view_modal" title="' . e(__('messages.edit')) . '">'
                            . '<i class="fa fa-edit" aria-hidden="true"></i> ' . e(__('messages.edit'))
                            . '</button>'
                            . '</div>';
                    }
                )
                ->editColumn('trial_end_date', function ($row) {
                    return !empty(data_get($row, 'trial_end_date')) ? $this->businessUtil->format_date(data_get($row, 'trial_end_date')) : '';
                })
                ->editColumn('start_date', function ($row) {
                    return !empty(data_get($row, 'start_date')) ? $this->businessUtil->format_date(data_get($row, 'start_date')) : '';
                })
                ->editColumn('end_date', function ($row) {
                    return !empty(data_get($row, 'end_date')) ? $this->businessUtil->format_date(data_get($row, 'end_date')) : '';
                })
                ->editColumn(
                    'status',
                    function ($row) {
                        if (data_get($row, 'status') == 'approved') {
                            return '<span class="label bg-light-green">' . e(__('superadmin::lang.' . data_get($row, 'status'))) . '</span>';
                        }

                        if (data_get($row, 'status') == 'waiting') {
                            return '<span class="label bg-aqua">' . e(__('superadmin::lang.' . data_get($row, 'status'))) . '</span>';
                        }

                        return '<span class="label bg-red">' . e(__('superadmin::lang.' . data_get($row, 'status'))) . '</span>';
                    }
                )
                ->editColumn(
                    'package_price',
                    function ($row) {
                        $currency_symbol = (string) data_get($row, 'currency_symbol', '');

                        return trim($currency_symbol . ' ' . data_get($row, 'package_price'));
                    }
                )
                ->editColumn(
                    'patient_code',
                    function ($row) {
                        if(data_get($row, 'is_patient')){
                            $html = data_get($row, 'patient_code');
                        }else{
                            $html = '';
                        }


                        return $html;
                    }
                )
                ->editColumn(
                    'business_name',
                    function ($row) {
                        $businessName = trim((string) data_get($row, 'business_name'));
                        $companyNumber = trim((string) data_get($row, 'company_number'));

                        if ($businessName === '') {
                            $businessName = $companyNumber;
                        }

                        if ($companyNumber !== '' && $companyNumber !== $businessName) {
                            $businessName = $companyNumber . ' - ' . $businessName;
                        }

                        if (empty(data_get($row, 'source_label'))) {
                            return e($businessName);
                        }

                        return e($businessName) . '<br><small class="text-muted">' . e(data_get($row, 'source_label')) . '</small>';
                    }
                )
                ->rawColumns(['business_name', 'status', 'action'])
                ->make(true);
        }
        
        $modules = collect($this->getModules())->mapWithKeys(function ($item) {
                        return [$item => $item];
                    })->toArray();

        $businesses = Business::pluck('name', 'id');
        
        return view('superadmin::superadmin_subscription.index')
        ->with(compact('modules', 'businesses'));;
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = request()->input('business_id');
        $packages = Package::active()->orderby('sort_order')->pluck('name', 'id');

        $gateways = $this->_payment_gateways();

        return view('superadmin::superadmin_subscription.add_subscription')
            ->with(compact('packages', 'business_id', 'gateways'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();
            $input = $request->only(['business_id', 'package_id', 'paid_via', 'payment_transaction_id']);
            $package = Package::find($input['package_id']);
            $user_id = $request->session()->get('user.id');

            $subscription =  $this->_add_subscription($input['business_id'], $package, $input['paid_via'], $input['payment_transaction_id'], $user_id, true);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return back()->with('status', $output);
    }
    
    public function getModules()
    {
        return array("Manufacturing Module",
                    "Accounting Module",
                    "Access Module",
                    "HR Module",
                    "Visitors Registration Module",
                    "Petro Module",
                    "Repair Module",
                    "Fleet Module",
                    "Mpcs Module",
                    "Backup Module",
                    "Property Module",
                    "Auto Repair Module",
                    "Contact Module",
                    "Ran Module",
                    "Report Module",
                    "Settings Module",
                    "User Management Module",
                    "Banking Module",
                    "Sale Module",
                    "Leads Module");
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('superadmin::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        DB::disableQueryLog();
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $status = Cache::remember('superadmin.package_subscription_status_options', 3600, function () {
            return Subscription::package_subscription_status();
        });
        $subscription = $this->findSubscriptionStatusForDisplay(
            (int) $id,
            request()->input('tenant_database'),
            request()->input('tenant_id')
        );

        return view('superadmin::superadmin_subscription.edit')
            ->with(compact('subscription', 'status'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        DB::disableQueryLog();
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $connectionName = $this->subscriptionConnectionName(
                $request->input('tenant_database'),
                $request->input('tenant_id')
            );

            $row = DB::connection($connectionName)
                ->table('subscriptions')
                ->select('id', 'business_id', 'package_id', 'status', 'paid_via', 'start_date')
                ->where('id', (int) $id)
                ->first();

            if (empty($row)) {
                abort(404, 'Subscription record was not found.');
            }

            $requestedStatus = strtolower(trim((string) $request->input('status', $row->status)));
            $updates = [
                'status' => $requestedStatus !== '' ? $requestedStatus : $row->status,
                'payment_transaction_id' => $request->input('payment_transaction_id'),
                'updated_at' => now(),
            ];

            if ($row->status === 'waiting' && $row->paid_via === 'offline' && empty($row->start_date) && $requestedStatus === 'approved') {
                $package = DB::connection($connectionName)->table('packages')->where('id', $row->package_id)->first();
                if ($package) {
                    $dates = $this->_get_package_dates((int) $row->business_id, $package);
                    $updates['start_date'] = $dates['start'];
                    $updates['end_date'] = $dates['end'];
                    $updates['trial_end_date'] = $dates['trial'];
                }
            }

            DB::connection($connectionName)->table('subscriptions')->where('id', (int) $id)->update($updates);

            $this->mirrorSubscriptionFieldsFast((int) $id, $connectionName, $request->input('tenant_database'), $request->input('tenant_id'), array_keys($updates));
            $this->releaseTemporarySubscriptionConnection($connectionName);

            return response()->json(['success' => true, 'msg' => __('superadmin::lang.subcription_updated_success')]);
        } catch (\Throwable $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function editSubscription($id)
    {
        DB::disableQueryLog();
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $subscription = $this->findSubscriptionForDisplay(
            (int) $id,
            request()->input('tenant_database'),
            request()->input('tenant_id')
        );

        return view('superadmin::superadmin_subscription.edit_date_modal')
            ->with(compact('subscription'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function updateSubscription(Request $request)
    {
        DB::disableQueryLog();
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $connectionName = $this->subscriptionConnectionName($request->input('tenant_database'), $request->input('tenant_id'));
            $subscriptionId = (int) $request->input('subscription_id');
            $updates = [
                'start_date' => $request->filled('start_date') ? $this->businessUtil->uf_date($request->input('start_date')) : null,
                'end_date' => $request->filled('end_date') ? $this->businessUtil->uf_date($request->input('end_date')) : null,
                'trial_end_date' => $request->filled('trial_end_date') ? $this->businessUtil->uf_date($request->input('trial_end_date')) : null,
                'updated_at' => now(),
            ];

            $exists = DB::connection($connectionName)->table('subscriptions')->where('id', $subscriptionId)->exists();
            if (!$exists) {
                abort(404, 'Subscription record was not found.');
            }
            DB::connection($connectionName)->table('subscriptions')->where('id', $subscriptionId)->update($updates);
            $this->mirrorSubscriptionFieldsFast($subscriptionId, $connectionName, $request->input('tenant_database'), $request->input('tenant_id'), array_keys($updates));
            $this->releaseTemporarySubscriptionConnection($connectionName);
            $output = ['success' => true, 'msg' => __('superadmin::lang.subcription_updated_success')];
        } catch (\Throwable $e) {
            \Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($output, $output['success'] ? 200 : 422);
        }
        return redirect()->to('/superadmin/packages')->with('status', $output);
    }

    protected function getSubscriptionRows(): array
    {
        $businessId = (int) request()->input('business_id');

        // The Package Subscription screen is opened inside a tenant very often.
        // Reading and merging both tenant and central subscription histories made
        // every DataTable request unnecessarily expensive.  The active scope is
        // now queried directly and only the latest row per business is returned.
        $rows = tenant()
            ? $this->fetchLatestRowsFromCurrentTenant($businessId ?: null)
            : $this->fetchLatestRowsFromBaseDatabase($businessId ?: null);

        return $this->attachCurrencySymbols($rows);
    }

    protected function fetchLatestRowsFromCurrentTenant(?int $businessId = null): array
    {
        return $this->fetchLatestRowsForConnection(DB::connection()->getName(), (string) (tenant()?->id ?: 'tenant'), DB::connection()->getDatabaseName(), false, $businessId);
    }

    protected function fetchLatestRowsFromBaseDatabase(?int $businessId = null): array
    {
        $connection = config('database.connections.system.database') ? 'system' : config('tenancy.database.central_connection', 'mysql');
        return $this->fetchLatestRowsForConnection($connection, 'base', (string) config('database.connections.system.database'), true, $businessId);
    }

    protected function fetchLatestRowsForConnection(string $connection, string $tenantId, string $tenantDatabase, bool $showDatabaseLabel, ?int $businessId = null): array
    {
        $latestIds = DB::connection($connection)->table('subscriptions')
            ->selectRaw('MAX(id) as id, business_id')
            ->groupBy('business_id');

        $rows = DB::connection($connection)->table('business')
            ->leftJoinSub($latestIds, 'latest_subscriptions', function ($join) {
                $join->on('business.id', '=', 'latest_subscriptions.business_id');
            })
            ->leftJoin('subscriptions', 'subscriptions.id', '=', 'latest_subscriptions.id')
            ->leftJoin('packages', 'subscriptions.package_id', '=', 'packages.id')
            ->leftJoin('users', 'business.owner_id', '=', 'users.id')
            ->when($businessId, function ($query) use ($businessId) {
                $query->where('business.id', $businessId);
            })
            ->select(
                'business.name as business_name', 'users.username as patient_code', 'packages.name as package_name',
                'subscriptions.status', 'subscriptions.start_date', 'subscriptions.trial_end_date', 'subscriptions.end_date',
                'subscriptions.package_price', 'subscriptions.paid_via', 'subscriptions.payment_transaction_id',
                'subscriptions.id', 'business.id as business_id', 'packages.currency_id', 'business.is_patient',
                'business.company_number', 'subscriptions.created_at'
            )
            ->orderBy('business.name')
            ->get();

        return $this->mapSubscriptionRows($rows, $tenantId, $tenantDatabase, $showDatabaseLabel);
    }

    protected function attachCurrencySymbols(array $rows): array
    {
        $currencyIds = collect($rows)->pluck('currency_id')->filter()->unique()->values();
        if ($currencyIds->isEmpty()) {
            return $rows;
        }

        $symbols = Currency::whereIn('id', $currencyIds)->pluck('symbol', 'id');

        return collect($rows)->map(function (array $row) use ($symbols) {
            $row['currency_symbol'] = (string) ($symbols[$row['currency_id']] ?? '');
            return $row;
        })->all();
    }

    /**
     * Keep only the latest subscription record for each business.
     * This prevents the Superadmin Package Subscription list from showing
     * the same business many times when it has older subscription history.
     */
    protected function getLatestSubscriptionRowPerBusiness(array $rows): array
    {
        return collect($rows)
            ->sortByDesc(function ($row) {
                $createdAt = strtotime((string) ($row['created_at'] ?? '1970-01-01 00:00:00'));
                $id = (int) ($row['id'] ?? 0);

                return $createdAt . '-' . str_pad((string) $id, 12, '0', STR_PAD_LEFT);
            })
            ->unique(function ($row) {
                // One visible current subscription per business.  The same
                // business can exist in both the central database and the
                // active tenant database during subscription syncing, so the
                // unique key must be the business identity, not the source DB.
                $companyNumber = trim((string) ($row['company_number'] ?? ''));
                $businessId = trim((string) ($row['business_id'] ?? ''));

                return $companyNumber !== ''
                    ? 'company|' . $companyNumber
                    : 'business|' . $businessId;
            })
            ->values()
            ->all();
    }

    protected function fetchRowsFromCurrentTenant(): array
    {
        $businessId = (int) request()->input('business_id');
        $companyNumber = (string) request()->input('company_number');
        $tenantId = tenant()?->id ?: 'tenant';
        $tenantDatabase = DB::connection()->getDatabaseName();

        $tenantBusinessQuery = DB::table('business')
            ->leftJoin('users', 'business.owner_id', '=', 'users.id')
            ->select(
                'business.id',
                'business.name',
                'business.company_number',
                'business.is_patient',
                'users.username as patient_code'
            );

        if (!empty($companyNumber)) {
            $tenantBusinessQuery->where('business.company_number', $companyNumber);
        } elseif (!empty($businessId)) {
            $tenantBusinessQuery->where('business.id', $businessId);
        }

        $tenantBusinesses = $tenantBusinessQuery->get();
        $tenantBusinessByCompany = $tenantBusinesses
            ->filter(function ($business) {
                return !empty($business->company_number);
            })
            ->keyBy('company_number');

        $tenantRowsQuery = DB::table('subscriptions')
            ->join('business', 'subscriptions.business_id', '=', 'business.id')
            ->leftJoin('users', 'business.owner_id', '=', 'users.id')
            ->select(
                'business.name as business_name',
                'users.username as patient_code',
                'subscriptions.package_id',
                'subscriptions.status',
                'subscriptions.start_date',
                'subscriptions.trial_end_date',
                'subscriptions.end_date',
                'subscriptions.package_price',
                'subscriptions.paid_via',
                'subscriptions.payment_transaction_id',
                'subscriptions.id',
                'subscriptions.business_id',
                'business.is_patient',
                'business.company_number as company_number',
                'subscriptions.created_at'
            );

        if (!empty($companyNumber)) {
            $tenantRowsQuery->where('business.company_number', $companyNumber);
        } elseif (!empty($businessId)) {
            $tenantRowsQuery->where('subscriptions.business_id', $businessId);
        }

        $tenantRows = $tenantRowsQuery
            ->orderByDesc('subscriptions.id')
            ->get();

        $rows = [];

        if ($tenantRows->isNotEmpty()) {
            $packages = $this->getPackagesByIds($tenantRows->pluck('package_id')->filter()->unique()->values()->all());

            $tenantRows = $tenantRows->map(function ($row) use ($packages) {
                $package = $packages->get($row->package_id);
                $row->package_name = $package->name ?? '';
                $row->currency_id = $package->currency_id ?? null;

                return $row;
            });

            $rows = array_merge(
                $rows,
                $this->mapSubscriptionRows($tenantRows, $tenantId, $tenantDatabase, false)
            );
        }

        // Packages assigned from Super Admin -> All Business -> Manage are
        // stored in the central database in many installations.  When viewing
        // the Package Subscription page inside a tenant domain, the tenant's
        // own subscriptions table can therefore be empty even though the
        // package was correctly assigned.  Use the tenant business
        // company_number values to pull the matching central subscription rows
        // and show only businesses belonging to the current tenant database.
        $companyNumbers = $tenantBusinessByCompany->keys()->filter()->values()->all();
        if (!empty($companyNumbers)) {
            $rows = array_merge(
                $rows,
                $this->fetchRowsFromCentralForCompanyNumbers($companyNumbers, $tenantBusinessByCompany)
            );
        }

        return $rows;
    }

    protected function getPackagesByIds(array $packageIds)
    {
        if (empty($packageIds)) {
            return collect();
        }

        try {
            return Package::withTrashed()
                ->whereIn('id', $packageIds)
                ->get(['id', 'name', 'currency_id'])
                ->keyBy('id');
        } catch (\Exception $e) {
            $centralConnection = config('database.connections.system.database') ? 'system' : config('tenancy.database.central_connection', 'mysql');

            return DB::connection($centralConnection)
                ->table('packages')
                ->whereIn('id', $packageIds)
                ->get(['id', 'name', 'currency_id'])
                ->keyBy('id');
        }
    }

    protected function fetchRowsFromCentralForCompanyNumbers(array $companyNumbers, $tenantBusinessByCompany): array
    {
        $centralConnection = config('database.connections.system.database') ? 'system' : config('tenancy.database.central_connection', 'mysql');
        $baseDatabase = config('database.connections.system.database');

        try {
            $centralRows = DB::connection($centralConnection)
                ->table('subscriptions')
                ->join('business', 'subscriptions.business_id', '=', 'business.id')
                ->leftJoin('packages', 'subscriptions.package_id', '=', 'packages.id')
                ->leftJoin('users', 'business.owner_id', '=', 'users.id')
                ->whereIn('business.company_number', $companyNumbers)
                ->select(
                    'business.name as business_name',
                    'users.username as patient_code',
                    'packages.name as package_name',
                    'subscriptions.status',
                    'subscriptions.start_date',
                    'subscriptions.trial_end_date',
                    'subscriptions.end_date',
                    'subscriptions.package_price',
                    'subscriptions.paid_via',
                    'subscriptions.payment_transaction_id',
                    'subscriptions.id',
                    'packages.currency_id',
                    'business.is_patient',
                    'business.company_number as company_number',
                    'business.id as central_business_id',
                    'subscriptions.business_id',
                    'subscriptions.created_at'
                )
                ->orderByDesc('subscriptions.id')
                ->get()
                ->map(function ($row) use ($tenantBusinessByCompany) {
                    $tenantBusiness = $tenantBusinessByCompany->get($row->company_number);
                    if (!empty($tenantBusiness)) {
                        // Display the current tenant's business identity while
                        // keeping the central subscription id/action target.
                        $row->business_name = $tenantBusiness->name ?: $row->business_name;
                        $row->patient_code = $tenantBusiness->patient_code ?: $row->patient_code;
                        $row->is_patient = $tenantBusiness->is_patient;
                        $row->business_id = $tenantBusiness->id;
                    }

                    return $row;
                });
        } catch (\Exception $e) {
            \Log::warning('Unable to load central package subscriptions for tenant scope: ' . $e->getMessage());
            return [];
        }

        return $this->mapSubscriptionRows($centralRows, 'base', $baseDatabase, false);
    }

    protected function fetchRowsFromBaseDatabase(?int $businessId = null): array
    {
        $baseDatabase = config('database.connections.system.database');

        $baseRowsQuery = Subscription::join('business', 'subscriptions.business_id', '=', 'business.id')
            ->leftJoin('packages', 'subscriptions.package_id', '=', 'packages.id')
            ->leftJoin('users', 'business.owner_id', '=', 'users.id')
            ->select(
                'business.name as business_name',
                'users.username as patient_code',
                'packages.name as package_name',
                'subscriptions.status',
                'subscriptions.start_date',
                'subscriptions.trial_end_date',
                'subscriptions.end_date',
                'subscriptions.package_price',
                'subscriptions.paid_via',
                'subscriptions.payment_transaction_id',
                'subscriptions.id',
                'packages.currency_id',
                'business.is_patient',
                'business.company_number as company_number',
                'subscriptions.created_at'
            );

        if (!empty($businessId)) {
            $baseRowsQuery->where('subscriptions.business_id', $businessId);
        }

        $baseRows = $baseRowsQuery
            ->orderByDesc('subscriptions.id')
            ->get();

        return $this->mapSubscriptionRows($baseRows, 'base', $baseDatabase, true);
    }

    protected function mapSubscriptionRows($rows, ?string $tenantId, ?string $tenantDatabase, bool $showDatabaseLabel): array
    {
        return collect($rows)->map(function ($row) use ($tenantId, $tenantDatabase, $showDatabaseLabel) {
            return [
                'business_name' => $row->business_name,
                'patient_code' => $row->patient_code,
                'package_name' => $row->package_name,
                'status' => $row->status,
                'start_date' => $row->start_date,
                'trial_end_date' => $row->trial_end_date,
                'end_date' => $row->end_date,
                'package_price' => $row->package_price,
                'paid_via' => $row->paid_via,
                'payment_transaction_id' => $row->payment_transaction_id,
                'id' => $row->id,
                'business_id' => $row->business_id,
                'currency_id' => $row->currency_id,
                'is_patient' => $row->is_patient,
                'company_number' => $row->company_number,
                'tenant_id' => $tenantId,
                'tenant_database' => $tenantDatabase,
                'source_label' => $showDatabaseLabel ? $this->formatSourceLabel($tenantId, $tenantDatabase) : null,
                'created_at' => $row->created_at,
            ];
        })->all();
    }

    protected function isTenantScopedSubscriptionRequest(?string $tenantDatabase = null, ?string $tenantId = null): bool
    {
        $baseDatabase = config('database.connections.system.database');

        return !empty($tenantDatabase)
            && $tenantId !== 'base'
            && $tenantDatabase !== $baseDatabase;
    }

    protected function formatSourceLabel(?string $tenantId, ?string $tenantDatabase): string
    {
        $parts = array_filter([$tenantId, $tenantDatabase]);

        return implode(' | ', $parts);
    }

    protected function syncTenantSubscriptionToCentralDatabase(Subscription $tenantSubscription, ?string $tenantDatabase = null): void
    {
        $centralConnection = config('database.connections.system.database') ? 'system' : config('tenancy.database.central_connection', 'mysql');
        $tenantDatabase = $tenantDatabase ?: DB::connection()->getDatabaseName();

        if (empty($tenantDatabase)) {
            return;
        }

        $tenantBusiness = $this->runOnMysqlDatabase($tenantDatabase, function ($connection) use ($tenantSubscription) {
            return $connection->table('business')
                ->select('id', 'company_number')
                ->where('id', $tenantSubscription->business_id)
                ->first();
        });

        if (empty($tenantBusiness) || empty($tenantBusiness->company_number)) {
            return;
        }

        $centralBusinessId = DB::connection($centralConnection)
            ->table('business')
            ->where('company_number', $tenantBusiness->company_number)
            ->value('id');

        if (empty($centralBusinessId)) {
            return;
        }

        $centralSubscription = Subscription::find($tenantSubscription->id);

        if (empty($centralSubscription)) {
            $centralSubscription = Subscription::where('business_id', $centralBusinessId)
                ->where('package_id', $tenantSubscription->package_id)
                ->orderByDesc('id')
                ->first();
        }

        if (empty($centralSubscription)) {
            return;
        }

        $centralSubscription->business_id = $centralBusinessId;
        $centralSubscription->package_id = $tenantSubscription->package_id;
        $centralSubscription->start_date = $tenantSubscription->getRawOriginal('start_date');
        $centralSubscription->trial_end_date = $tenantSubscription->getRawOriginal('trial_end_date');
        $centralSubscription->end_date = $tenantSubscription->getRawOriginal('end_date');
        $centralSubscription->package_price = $tenantSubscription->package_price;
        $centralSubscription->paid_via = $tenantSubscription->paid_via;
        $centralSubscription->payment_transaction_id = $tenantSubscription->payment_transaction_id;
        $centralSubscription->status = $tenantSubscription->status;
        $centralSubscription->save();

        $this->syncSubscriptionToTenantDatabases($centralSubscription);
    }

    protected function findSubscriptionStatusForDisplay(int $subscriptionId, ?string $tenantDatabase = null, ?string $tenantId = null)
    {
        $connectionName = $this->subscriptionConnectionName($tenantDatabase, $tenantId);
        try {
            $subscription = DB::connection($connectionName)->table('subscriptions')
                ->select('id', 'status', 'payment_transaction_id')->where('id', $subscriptionId)->first();
            if (empty($subscription)) {
                abort(404, 'Subscription record was not found.');
            }
            return $subscription;
        } finally {
            $this->releaseTemporarySubscriptionConnection($connectionName);
        }
    }

    protected function mirrorSubscriptionFieldsFast(int $subscriptionId, string $sourceConnection, ?string $tenantDatabase, ?string $tenantId, array $fields): void
    {
        if (!$this->isTenantScopedSubscriptionRequest($tenantDatabase, $tenantId)) {
            return;
        }
        $source = DB::connection($sourceConnection)
            ->table('subscriptions')
            ->join('business', 'subscriptions.business_id', '=', 'business.id')
            ->where('subscriptions.id', $subscriptionId)
            ->select('subscriptions.*', 'business.company_number')
            ->first();
        if (!$source || empty($source->company_number)) return;

        $centralConnection = config('database.connections.system.database') ? 'system' : config('tenancy.database.central_connection', 'mysql');
        $centralId = DB::connection($centralConnection)
            ->table('subscriptions')
            ->join('business', 'subscriptions.business_id', '=', 'business.id')
            ->where('business.company_number', $source->company_number)
            ->orderByDesc('subscriptions.id')
            ->value('subscriptions.id');
        if (!$centralId) return;
        $allowed = ['status','payment_transaction_id','start_date','end_date','trial_end_date','updated_at'];
        $mirror = [];
        foreach (array_intersect($fields, $allowed) as $field) $mirror[$field] = $source->{$field} ?? null;
        if ($mirror) DB::connection($centralConnection)->table('subscriptions')->where('id', $centralId)->update($mirror);
    }

    protected function findSubscriptionForDisplay(int $subscriptionId, ?string $tenantDatabase = null, ?string $tenantId = null)
    {
        $connectionName = $this->subscriptionConnectionName($tenantDatabase, $tenantId);

        try {
            $subscription = DB::connection($connectionName)
                ->table('subscriptions')
                ->select('id', 'start_date', 'end_date', 'trial_end_date')
                ->where('id', $subscriptionId)
                ->first();

            if (empty($subscription)) {
                abort(404, 'Subscription record was not found.');
            }

            return $subscription;
        } finally {
            $this->releaseTemporarySubscriptionConnection($connectionName);
        }
    }

    protected function findSubscription(int $subscriptionId, ?string $tenantDatabase = null, ?string $tenantId = null): Subscription
    {
        $connectionName = $this->subscriptionConnectionName($tenantDatabase, $tenantId);

        try {
            $subscription = (new Subscription())
                ->setConnection($connectionName)
                ->newQuery()
                ->find($subscriptionId);

            if (empty($subscription)) {
                abort(404, 'Subscription record was not found.');
            }

            return $subscription;
        } catch (\Throwable $e) {
            $this->releaseTemporarySubscriptionConnection($connectionName);
            throw $e;
        }
    }

    /**
     * Resolve the exact database connection that owns the subscription row.
     *
     * Package subscriptions can be listed from the central database while the
     * request itself is running on a tenant host. Falling back to Laravel's
     * current default connection therefore opens the wrong database and made
     * the Edit modal return HTTP 500. This method always selects either the
     * central connection or an isolated temporary tenant connection.
     */
    protected function subscriptionConnectionName(?string $tenantDatabase = null, ?string $tenantId = null): string
    {
        $centralConnection = config('database.connections.system.database')
            ? 'system'
            : config('tenancy.database.central_connection', 'mysql');
        $baseDatabase = (string) config('database.connections.system.database');

        if (
            empty($tenantDatabase)
            || $tenantId === 'base'
            || ($baseDatabase !== '' && $tenantDatabase === $baseDatabase)
        ) {
            return $centralConnection;
        }

        // When the form is opened from a tenant host, Laravel is already
        // connected to that tenant database. Reusing the live connection
        // avoids a purge/reconnect cycle for every Edit/Status click and save.
        try {
            $currentConnection = DB::getDefaultConnection();
            $currentDatabase = (string) DB::connection($currentConnection)->getDatabaseName();
            if ($currentDatabase !== '' && strcasecmp($currentDatabase, (string) $tenantDatabase) === 0) {
                return $currentConnection;
            }
        } catch (\Throwable $e) {
            // Fall through to an isolated lookup connection.
        }

        $connectionName = 'superadmin_subscription_tenant_lookup_' . substr(sha1((string) $tenantDatabase), 0, 10);

        if (!config("database.connections.{$connectionName}")) {
            $baseConfig = config('database.connections.mysql', []);
            $baseConfig['database'] = $tenantDatabase;
            config(["database.connections.{$connectionName}" => $baseConfig]);
        }

        return $connectionName;
    }

    protected function releaseTemporarySubscriptionConnection(string $connectionName): void
    {
        // Reused current/central connections must stay open. Temporary lookup
        // connections are also retained for the lifetime of this PHP request so
        // opening and saving a modal does not pay repeated reconnect costs.
        if (!str_starts_with($connectionName, 'superadmin_subscription_tenant_lookup_')) {
            return;
        }
    }

    protected function runOnMysqlDatabase(string $databaseName, callable $callback)
    {
        $originalDatabase = config('database.connections.mysql.database');

        try {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $databaseName]);
            DB::reconnect('mysql');

            return $callback(DB::connection('mysql'));
        } finally {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $originalDatabase]);
            DB::reconnect('mysql');
        }
    }
}
