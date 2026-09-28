<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\MembershipNew\app\Models\MembershipNewRegion;
use Modules\MembershipNew\app\Models\MembershipNewSettingOption;
use Modules\MembershipNew\app\Support\MembershipNewContext;
use Modules\MembershipNew\app\Utils\MembershipNewFormatUtil;

class MembershipNewSettingsController extends Controller
{
    private const SETTING_GROUPS = [
        'membership_type',
        'membership_status',
        'renewal_period',
        'registration_renewal_amount',
    ];

    private const TABS = [
        'regions',
        'membership-types',
        'membership-status',
        'renewal-period',
        'registration-renewal-amount',
    ];

    public function index(Request $request)
    {
        $businessId = MembershipNewContext::businessId();
        [$fromDate, $toDate, $dateRange] = $this->resolveDateRange($request);

        // Keep Regions lean: simplePaginate avoids a second COUNT(*) query and
        // direct DATE comparisons allow the business/date index to be used.
        $query = MembershipNewRegion::query()
            ->select(['id', 'date', 'region_no', 'region', 'added_by', 'created_by', 'created_at', 'is_active'])
            ->forBusiness($businessId)
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($fromDate) {
            $query->where('date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('date', '<=', $toDate);
        }
        if ($request->filled('region_no')) {
            $query->where('region_no', 'like', '%' . trim((string) $request->input('region_no')) . '%');
        }
        if ($request->filled('region')) {
            $query->where('region', 'like', '%' . trim((string) $request->input('region')) . '%');
        }
        if ($request->filled('added_by')) {
            $query->where('added_by', 'like', '%' . trim((string) $request->input('added_by')) . '%');
        }

        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        $regions = $query->simplePaginate($perPage)->appends($request->query());
        $this->attachCreatorNames($regions);

        // All four new settings tabs are loaded using one small indexed query.
        // This keeps tab changes instant and avoids an AJAX request just to open a tab.
        $settingOptions = collect();
        if (Schema::hasTable('mn_setting_options')) {
            $settingOptions = MembershipNewSettingOption::query()
                ->select(['id', 'setting_group', 'setting_value', 'amount', 'added_by', 'created_by', 'created_at', 'is_active'])
                ->forBusiness($businessId)
                ->whereIn('setting_group', self::SETTING_GROUPS)
                ->orderByDesc('id')
                ->get()
                ->groupBy('setting_group');
        }

        $activeTab = (string) $request->input('tab', 'regions');
        if (!in_array($activeTab, self::TABS, true)) {
            $activeTab = 'regions';
        }

        return view('membershipnew::settings.index', [
            'regions' => $regions,
            'settingOptions' => $settingOptions,
            'activeTab' => $activeTab,
            'dateRange' => $dateRange,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'today' => now()->toDateString(),
            'perPage' => $perPage,
            'renewalPeriods' => [
                'daily' => 'Daily',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
                'annually' => 'Annually',
            ],
        ]);
    }

    public function storeRegion(Request $request)
    {
        $businessId = MembershipNewContext::businessId();

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'region_no' => ['required', 'string', 'max:50'],
            'region' => ['required', 'string', 'max:150'],
        ]);

        $user = Auth::user();

        try {
            $region = MembershipNewRegion::create([
                'business_id' => $businessId,
                'date' => $validated['date'],
                'region_no' => trim($validated['region_no']),
                'region' => trim($validated['region']),
                'added_by' => $this->userNameOnly($user) ?: null,
                'created_by' => $user ? (int) $user->getAuthIdentifier() : null,
                'is_active' => true,
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                throw ValidationException::withMessages([
                    'region_no' => ['This Region No already exists for the selected business.'],
                ]);
            }

            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Region added successfully.',
                'kind' => 'region',
                'row' => $this->regionPayload($region->fresh(), $this->userNameOnly($user)),
            ]);
        }

        return redirect()
            ->route('membership-new.settings.index', ['tab' => 'regions'])
            ->with('status', 'Region added successfully.');
    }

    public function storeOption(Request $request)
    {
        $businessId = MembershipNewContext::businessId();

        if (!Schema::hasTable('mn_setting_options')) {
            throw ValidationException::withMessages([
                'setting_group' => ['Membership Settings database update has not been applied yet.'],
            ]);
        }

        $base = $request->validate([
            'setting_group' => ['required', Rule::in(self::SETTING_GROUPS)],
        ]);

        $group = (string) $base['setting_group'];
        $user = Auth::user();
        $value = null;
        $amount = null;
        $key = null;
        $message = 'Setting added successfully.';
        $tableId = '';
        $columns = [];
        $tab = 'regions';

        switch ($group) {
            case 'membership_type':
                $data = $request->validate([
                    'setting_value' => ['required', 'string', 'max:150'],
                ]);
                $value = $this->cleanText($data['setting_value']);
                $key = $this->normaliseKey($value);
                $message = 'Membership Type added successfully.';
                $tableId = 'mn-membership-types-table';
                $tab = 'membership-types';
                $columns = [$value, $this->userNameOnly($user)];
                break;

            case 'membership_status':
                $data = $request->validate([
                    'setting_value' => ['required', 'string', 'max:100'],
                ]);
                $value = $this->cleanText($data['setting_value']);
                $key = $this->normaliseKey($value);
                $message = 'Membership Status added successfully.';
                $tableId = 'mn-membership-status-table';
                $tab = 'membership-status';
                $columns = [$value, $this->userNameOnly($user)];
                break;

            case 'renewal_period':
                $data = $request->validate([
                    'setting_value' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'annually'])],
                ]);
                $key = strtolower((string) $data['setting_value']);
                $value = ucfirst($key);
                $message = 'Renewal Period added successfully.';
                $tableId = 'mn-renewal-period-table';
                $tab = 'renewal-period';
                $columns = [$value, $this->userNameOnly($user)];
                break;

            case 'registration_renewal_amount':
                $data = $request->validate([
                    'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999999999.9999'],
                ]);
                $amount = array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== ''
                    ? number_format((float) $data['amount'], 4, '.', '')
                    : null;
                $key = $amount === null ? 'amount:none' : 'amount:' . $amount;
                $message = 'Registration / Renewal Amount added successfully.';
                $tableId = 'mn-registration-renewal-amount-table';
                $tab = 'registration-renewal-amount';
                $columns = [$amount === null ? '' : \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($amount), $this->userNameOnly($user)];
                break;
        }

        try {
            $option = MembershipNewSettingOption::create([
                'business_id' => $businessId,
                'setting_group' => $group,
                'setting_key' => $key,
                'setting_value' => $value,
                'amount' => $amount,
                'added_by' => $this->userNameOnly($user) ?: null,
                'created_by' => $user ? (int) $user->getAuthIdentifier() : null,
                'is_active' => true,
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyException($e)) {
                throw ValidationException::withMessages([
                    'setting_value' => [$this->duplicateMessage($group)],
                ]);
            }

            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'kind' => $group === 'membership_type' ? 'membership_type' : 'option',
                'table_id' => $tableId,
                'columns' => $columns,
                'row' => $this->optionPayload($option->fresh(), $this->userNameOnly($user)),
                'id' => (int) $option->id,
            ]);
        }

        return redirect()
            ->route('membership-new.settings.index', ['tab' => $tab])
            ->with('status', $message);
    }

    public function updateRegion(Request $request, int $regionId)
    {
        $businessId = MembershipNewContext::businessId();
        $region = MembershipNewRegion::query()->forBusiness($businessId)->findOrFail($regionId);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'region_no' => [
                'required',
                'string',
                'max:50',
                Rule::unique('mn_regions', 'region_no')
                    ->where(fn ($query) => $query->where('business_id', $businessId)->whereNull('deleted_at'))
                    ->ignore($region->id),
            ],
            'region' => ['required', 'string', 'max:150'],
        ]);

        $region->date = $validated['date'];
        $region->region_no = trim($validated['region_no']);
        $region->region = trim($validated['region']);
        $region->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Region updated successfully.',
                'kind' => 'region',
                'row' => $this->regionPayload($region->fresh()),
            ]);
        }

        return redirect()->route('membership-new.settings.index', ['tab' => 'regions'])
            ->with('status', 'Region updated successfully.');
    }

    public function toggleRegionStatus(Request $request, int $regionId)
    {
        $businessId = MembershipNewContext::businessId();
        $region = MembershipNewRegion::query()->forBusiness($businessId)->findOrFail($regionId);
        $region->is_active = !$region->is_active;
        $region->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Region status changed successfully.',
                'kind' => 'region',
                'row' => $this->regionPayload($region->fresh()),
            ]);
        }

        return redirect()->route('membership-new.settings.index', ['tab' => 'regions'])
            ->with('status', 'Region status changed successfully.');
    }

    public function updateOption(Request $request, int $optionId)
    {
        $businessId = MembershipNewContext::businessId();
        $option = MembershipNewSettingOption::query()->forBusiness($businessId)->findOrFail($optionId);

        if (!in_array($option->setting_group, self::SETTING_GROUPS, true)) {
            abort(404);
        }

        $group = (string) $option->setting_group;
        $value = null;
        $amount = null;
        $key = null;
        $message = 'Setting updated successfully.';
        $tab = 'regions';

        switch ($group) {
            case 'membership_type':
                $data = $request->validate([
                    'setting_value' => ['required', 'string', 'max:150'],
                ]);
                $value = $this->cleanText($data['setting_value']);
                $key = $this->normaliseKey($value);
                $message = 'Membership Type updated successfully.';
                $tab = 'membership-types';
                break;

            case 'membership_status':
                $data = $request->validate([
                    'setting_value' => ['required', 'string', 'max:100'],
                ]);
                $value = $this->cleanText($data['setting_value']);
                $key = $this->normaliseKey($value);
                $message = 'Membership Status updated successfully.';
                $tab = 'membership-status';
                break;

            case 'renewal_period':
                $data = $request->validate([
                    'setting_value' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'annually'])],
                ]);
                $key = strtolower((string) $data['setting_value']);
                $value = ucfirst($key);
                $message = 'Renewal Period updated successfully.';
                $tab = 'renewal-period';
                break;

            case 'registration_renewal_amount':
                $data = $request->validate([
                    'amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999999999.9999'],
                ]);
                $amount = array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== ''
                    ? number_format((float) $data['amount'], 4, '.', '')
                    : null;
                $key = $amount === null ? 'amount:none' : 'amount:' . $amount;
                $message = 'Registration / Renewal Amount updated successfully.';
                $tab = 'registration-renewal-amount';
                break;
        }

        $duplicate = MembershipNewSettingOption::query()
            ->forBusiness($businessId)
            ->where('setting_group', $group)
            ->where('setting_key', $key)
            ->where('id', '<>', $option->id)
            ->exists();

        if ($duplicate) {
            $field = $group === 'registration_renewal_amount' ? 'amount' : 'setting_value';
            throw ValidationException::withMessages([
                $field => [$this->duplicateMessage($group)],
            ]);
        }

        $option->setting_key = $key;
        $option->setting_value = $value;
        $option->amount = $amount;
        $option->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'kind' => 'option',
                'row' => $this->optionPayload($option->fresh()),
            ]);
        }

        return redirect()->route('membership-new.settings.index', ['tab' => $tab])
            ->with('status', $message);
    }

    public function toggleOptionStatus(Request $request, int $optionId)
    {
        $businessId = MembershipNewContext::businessId();
        $option = MembershipNewSettingOption::query()->forBusiness($businessId)->findOrFail($optionId);

        if (!in_array($option->setting_group, self::SETTING_GROUPS, true)) {
            abort(404);
        }

        $option->is_active = !$option->is_active;
        $option->save();

        $labels = [
            'membership_type' => ['Membership Type', 'membership-types'],
            'membership_status' => ['Membership Status', 'membership-status'],
            'renewal_period' => ['Renewal Period', 'renewal-period'],
            'registration_renewal_amount' => ['Registration / Renewal Amount', 'registration-renewal-amount'],
        ];
        [$label, $tab] = $labels[$option->setting_group] ?? ['Setting', 'regions'];
        $message = $label . ' status changed successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'kind' => 'option',
                'row' => $this->optionPayload($option->fresh()),
            ]);
        }

        return redirect()->route('membership-new.settings.index', ['tab' => $tab])
            ->with('status', $message);
    }

    private function regionPayload(MembershipNewRegion $region, ?string $addedBy = null): array
    {
        return [
            'id' => (int) $region->id,
            'date' => optional($region->date)->format('Y-m-d') ?: '',
            'date_time' => MembershipNewFormatUtil::dateTime($region->date, $region->created_at),
            'region_no' => (string) $region->region_no,
            'region' => (string) $region->region,
            'added_by' => $addedBy ?? MembershipNewFormatUtil::addedBy($region),
            'is_active' => (bool) $region->is_active,
            'status' => $region->is_active ? 'Enabled' : 'Disabled',
            'update_url' => route('membership-new.settings.regions.update', $region->id),
            'toggle_url' => route('membership-new.settings.regions.toggle-status', $region->id),
        ];
    }

    private function optionPayload(MembershipNewSettingOption $option, ?string $addedBy = null): array
    {
        return [
            'id' => (int) $option->id,
            'setting_group' => (string) $option->setting_group,
            'setting_key' => (string) ($option->setting_key ?? ''),
            'setting_value' => (string) ($option->setting_value ?? ''),
            'amount' => $option->amount === null ? null : (string) $option->amount,
            'date_time' => MembershipNewFormatUtil::dateTime($option->created_at),
            'added_by' => $addedBy ?? MembershipNewFormatUtil::addedBy($option),
            'is_active' => (bool) $option->is_active,
            'status' => $option->is_active ? 'Enabled' : 'Disabled',
            'update_url' => route('membership-new.settings.options.update', $option->id),
            'toggle_url' => route('membership-new.settings.options.toggle-status', $option->id),
        ];
    }

    private function duplicateMessage(string $group): string
    {
        return match ($group) {
            'membership_type' => 'This Membership Type already exists for the selected business.',
            'membership_status' => 'This Membership Status already exists for the selected business.',
            'renewal_period' => 'This Renewal Period has already been added for the selected business.',
            'registration_renewal_amount' => 'This Registration / Renewal Amount has already been added for the selected business.',
            default => 'This setting already exists for the selected business.',
        };
    }

    private function cleanText($value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', (string) $value));
    }

    private function normaliseKey(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        $info = $e->errorInfo;
        $sqlState = isset($info[0]) ? (string) $info[0] : (string) $e->getCode();
        $driverCode = isset($info[1]) ? (int) $info[1] : 0;

        return $driverCode === 1062 || $sqlState === '23000' || $sqlState === '23505';
    }

    private function resolveDateRange(Request $request): array
    {
        $dateRange = (string) $request->input('date_range', 'this_year');
        $allowed = ['this_year', 'last_year', 'this_fy', 'last_fy', 'custom', 'all'];
        if (!in_array($dateRange, $allowed, true)) {
            $dateRange = 'this_year';
        }

        $today = now()->startOfDay();
        $from = null;
        $to = null;

        switch ($dateRange) {
            case 'this_year':
                $from = $today->copy()->startOfYear();
                $to = $today->copy()->endOfYear();
                break;
            case 'last_year':
                $from = $today->copy()->subYear()->startOfYear();
                $to = $today->copy()->subYear()->endOfYear();
                break;
            case 'this_fy':
                [$from, $to] = $this->financialYearRange($today, 0);
                break;
            case 'last_fy':
                [$from, $to] = $this->financialYearRange($today, -1);
                break;
            case 'custom':
                $from = $this->parseDate($request->input('from_date'));
                $to = $this->parseDate($request->input('to_date'));
                if ($from && $to && $from->gt($to)) {
                    [$from, $to] = [$to, $from];
                }
                break;
            case 'all':
            default:
                break;
        }

        return [
            $from ? $from->toDateString() : null,
            $to ? $to->toDateString() : null,
            $dateRange,
        ];
    }

    private function financialYearRange(Carbon $today, int $yearOffset): array
    {
        $startMonth = (int) (session('business.fy_start_month') ?: session('fy_start_month') ?: 1);
        if ($startMonth < 1 || $startMonth > 12) {
            $startMonth = 1;
        }

        $startYear = $today->month < $startMonth ? $today->year - 1 : $today->year;
        $startYear += $yearOffset;

        $from = Carbon::create($startYear, $startMonth, 1, 0, 0, 0, $today->getTimezone())->startOfDay();
        $to = $from->copy()->addYear()->subDay()->endOfDay();

        return [$from, $to];
    }

    private function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, (string) $value)->startOfDay();
            } catch (\Throwable $e) {
                // Try the next supported system date format.
            }
        }

        return null;
    }

    /**
     * Attach only the real user name to each Region row. Never expose email,
     * username, numeric ID, or a generic label in the Added By column.
     */
    private function attachCreatorNames($regions): void
    {
        $collection = $regions->getCollection();
        $creatorIds = $collection->pluck('created_by')->filter()->unique()->values();

        if ($creatorIds->isEmpty()) {
            foreach ($collection as $region) {
                $region->setAttribute('added_by_name', '');
            }
            return;
        }

        $authUser = Auth::user();
        if (!$authUser) {
            foreach ($collection as $region) {
                $region->setAttribute('added_by_name', '');
            }
            return;
        }

        $userClass = get_class($authUser);
        $keyName = $authUser->getKeyName();
        $users = $userClass::query()->whereIn($keyName, $creatorIds->all())->get();

        $names = [];
        foreach ($users as $user) {
            $names[(string) $user->getKey()] = $this->userNameOnly($user);
        }

        foreach ($collection as $region) {
            $region->setAttribute(
                'added_by_name',
                $region->created_by ? ($names[(string) $region->created_by] ?? '') : ''
            );
        }
    }

    /**
     * Return a person's actual name only. No username/email/ID/System fallback.
     */
    private function userNameOnly($user): string
    {
        if (!$user) {
            return '';
        }

        $parts = array_filter([
            $user->surname ?? null,
            $user->first_name ?? null,
            $user->last_name ?? null,
        ], static function ($value) {
            return $value !== null && trim((string) $value) !== '';
        });

        $name = trim(implode(' ', $parts));
        if ($name !== '') {
            return $name;
        }

        if (isset($user->name) && trim((string) $user->name) !== '') {
            return trim((string) $user->name);
        }

        return '';
    }
}
