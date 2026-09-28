<?php

namespace Modules\Subscription\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Subscription\Entities\EstateSubscription;
use Modules\Subscription\Entities\EstateSubscriptionReminder;
use Modules\Subscription\Services\CentralRegistryService;

class EstateSubscriptionController extends Controller
{
    protected $registry;
    public function __construct(CentralRegistryService $registry)
    {
        $this->registry = $registry;
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->can('superadmin')) {
                abort(403, 'Super Admin authority is required.');
            }
            return $next($request);
        });
    }

    protected function centralModel()
    {
        return (new EstateSubscription)->setConnection($this->registry->centralConnectionName());
    }

    public function index(Request $request)
    {
        $connection = $this->registry->centralConnectionName();
        $query = $this->centralModel()->newQuery();
        if ($request->filled('start_date')) $query->whereDate('business_registered_on', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('business_registered_on', '<=', $request->end_date);
        if ($request->filled('tenant_id') && $request->tenant_id !== CentralRegistryService::ALL) $query->where('tenant_id', $request->tenant_id);
        if ($request->filled('business_key')) {
            $parts = explode('::', (string) $request->business_key, 2);
            if (count($parts) === 2) {
                $query->where('tenant_id', $parts[0])->where('business_registry_id', (int) $parts[1]);
            }
        }
        $subscriptions = $query->orderByDesc('id')->paginate(50)->appends($request->query());
        $tenants = $this->registry->tenants();
        $businessFilterOptions = $this->centralModel()->newQuery()
            ->select(['tenant_id', 'business_registry_id', 'business_name'])
            ->distinct()->orderBy('business_name')->get();
        return view('subscription::estate.index', compact('subscriptions', 'tenants', 'connection', 'businessFilterOptions'));
    }

    public function create()
    {
        return view('subscription::estate.form', [
            'subscription' => null,
            'tenants' => $this->registry->tenants(),
            'businesses' => collect(),
            'selectedTenantIds' => [CentralRegistryService::ALL],
        ]);
    }

    public function businessOptions(Request $request)
    {
        $tenantIds = (array) $request->input('tenant_ids', [CentralRegistryService::ALL]);
        $rows = $this->registry->businesses($tenantIds)->map(function ($row) {
            return [
                'id' => (string) $row->selection_id,
                'tenant_id' => (string) $row->tenant_id,
                'global_uid' => (string) ($row->global_uid ?? ''),
                'text' => '[' . $row->tenant_id . '] ' . $row->name . (!empty($row->company_number) ? ' · ' . $row->company_number : ''),
            ];
        })->values();
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $validator = $this->validatePayload($request, true);
        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();

        $tenantIds = $this->registry->tenantUidsFromSelection((array) $request->tenant_ids);
        $allBusinesses = $request->boolean('all_businesses');
        $selectedBusinessKeys = array_values(array_filter(array_map('strval', (array) $request->business_ids)));
        $businessRows = $this->registry->businesses($tenantIds)->filter(function ($row) use ($allBusinesses, $selectedBusinessKeys) {
            return $allBusinesses || in_array((string) $row->selection_id, $selectedBusinessKeys, true);
        });
        if ($businessRows->isEmpty()) return redirect()->back()->withErrors(['business_ids' => 'Please select at least one business.'])->withInput();

        $connection = $this->registry->centralConnectionName();
        DB::connection($connection)->transaction(function () use ($businessRows, $request, $connection) {
            foreach ($businessRows as $business) {
                $registered = Carbon::createFromFormat('Y-m-d', $request->business_registered_on)->startOfDay();
                $expiry = $registered->copy()->addDays((int) $request->subscription_period_days);
                $tenantDb = $this->registry->tenantDatabase($business->tenant_id);
                $record = (new EstateSubscription)->setConnection($connection);
                $record->fill([
                    'tenant_id' => (string) $business->tenant_id,
                    'tenant_database' => $tenantDb,
                    'business_registry_id' => (int) $business->id,
                    'business_global_uid' => (string) ($business->global_uid ?? ''),
                    'business_name' => (string) $business->name,
                    'business_registered_on' => $registered->toDateString(),
                    'subscription_period_days' => (int) $request->subscription_period_days,
                    'subscription_amount' => (float) $request->subscription_amount,
                    'business_mobile_numbers' => $this->normalizeNumbers($request->business_mobile_numbers),
                    'expiry_date' => $expiry->toDateString(),
                    'status' => 1,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                $record->save();
                $this->saveReminders($record, $request, $connection);
            }
        });
        return redirect()->route('subscription.estate.index')->with('status', ['success' => true, 'msg' => 'Subscription details saved successfully.']);
    }

    public function edit($id)
    {
        $subscription = $this->centralModel()->newQuery()->findOrFail($id);
        $subscription->setRelation('reminders', (new EstateSubscriptionReminder)->setConnection($this->registry->centralConnectionName())->newQuery()->where('subscription_id', $id)->orderBy('reminder_no')->get());
        return view('subscription::estate.form', [
            'subscription' => $subscription,
            'tenants' => $this->registry->tenants(),
            'businesses' => $this->registry->businesses([$subscription->tenant_id]),
            'selectedTenantIds' => [$subscription->tenant_id],
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = $this->validatePayload($request, false);
        if ($validator->fails()) return redirect()->back()->withErrors($validator)->withInput();
        $connection = $this->registry->centralConnectionName();
        $record = $this->centralModel()->newQuery()->findOrFail($id);
        $registered = Carbon::createFromFormat('Y-m-d', $request->business_registered_on)->startOfDay();
        $record->fill([
            'business_registered_on' => $registered->toDateString(),
            'subscription_period_days' => (int) $request->subscription_period_days,
            'subscription_amount' => (float) $request->subscription_amount,
            'business_mobile_numbers' => $this->normalizeNumbers($request->business_mobile_numbers),
            'expiry_date' => $registered->copy()->addDays((int) $request->subscription_period_days)->toDateString(),
            'updated_by' => auth()->id(),
        ])->save();
        $this->saveReminders($record, $request, $connection);
        return redirect()->route('subscription.estate.index')->with('status', ['success' => true, 'msg' => 'Subscription details updated successfully.']);
    }

    public function destroy($id)
    {
        $connection = $this->registry->centralConnectionName();
        DB::connection($connection)->transaction(function () use ($id, $connection) {
            DB::connection($connection)->table('subs_reminder_logs')->where('subscription_id', $id)->delete();
            DB::connection($connection)->table('subs_subscription_reminders')->where('subscription_id', $id)->delete();
            DB::connection($connection)->table('subs_business_subscriptions')->where('id', $id)->delete();
        });
        return redirect()->back()->with('status', ['success' => true, 'msg' => 'Subscription details deleted successfully.']);
    }

    protected function validatePayload(Request $request, $isCreate)
    {
        $rules = [
            'business_registered_on' => 'required|date_format:Y-m-d',
            'subscription_period_days' => 'required|integer|min:1|max:36500',
            'subscription_amount' => 'required|numeric|min:0',
            'business_mobile_numbers' => ['required', 'string', 'max:2000', 'regex:/^[0-9+ ,\-()]+$/'],
            'reminder_1_days' => 'nullable|integer|min:0|max:36500',
            'reminder_1_message' => 'nullable|string|max:2000',
            'reminder_2_days' => 'nullable|integer|min:0|max:36500',
            'reminder_2_message' => 'nullable|string|max:2000',
        ];
        if ($isCreate) {
            $rules['tenant_ids'] = 'required|array|min:1';
        }
        return Validator::make($request->all(), $rules);
    }

    protected function saveReminders($record, Request $request, $connection)
    {
        foreach ([1, 2] as $no) {
            $days = $request->input('reminder_' . $no . '_days');
            $message = trim((string) $request->input('reminder_' . $no . '_message'));
            if ($message !== '') {
                $required = [];
                if (strpos($message, '{subscription_amount}') === false) $required[] = 'Subscription Amount: {subscription_amount}';
                if (strpos($message, '{system_expiry_date}') === false) $required[] = 'System Expiry Date: {system_expiry_date}';
                if (!empty($required)) $message = implode("\n", $required) . "\n" . $message;
            }
            $row = (new EstateSubscriptionReminder)->setConnection($connection)->newQuery()->firstOrNew([
                'subscription_id' => $record->id,
                'reminder_no' => $no,
            ]);
            $row->days_before = $days === null || $days === '' ? null : (int) $days;
            $row->message_body = $message;
            $row->is_enabled = ($row->days_before !== null && $message !== '') ? 1 : 0;
            $row->save();
        }
    }

    protected function normalizeNumbers($value)
    {
        $numbers = preg_split('/\s*,\s*/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
        $numbers = array_values(array_unique(array_map(function ($n) { return preg_replace('/[^0-9+]/', '', $n); }, $numbers)));
        return implode(',', array_filter($numbers));
    }
}
