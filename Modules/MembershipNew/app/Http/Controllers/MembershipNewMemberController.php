<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\MembershipNew\app\Models\MembershipNewMember;
use Modules\MembershipNew\app\Models\MembershipNewRegion;
use Modules\MembershipNew\app\Models\MembershipNewSettingOption;
use Modules\MembershipNew\app\Services\MembershipNewMemberService;
use Modules\MembershipNew\app\Services\MembershipNewPointService;
use Modules\MembershipNew\app\Support\MembershipNewContext;

class MembershipNewMemberController extends Controller
{
    private const TITLES = [
        'Mr.', 'Mrs.', 'Ms.', 'Miss', 'Master', 'Dr.', 'Prof.', 'Rev.', 'Ven.',
        'Hon.', 'Eng.', 'Fr.', 'Sr.', 'Capt.', 'Major', 'Col.', 'Lt. Col.', 'Sir', 'Madam', 'Mx.', 'Other',
    ];

    private const GENDERS = ['Male', 'Female', 'Other', 'Prefer not to say'];

    public function index(Request $request)
    {
        $businessId = MembershipNewContext::businessId();

        // The Members list displays the member's own no_of_shares field.
        // Do not eager-load shareHolding here: latestOfMany() introduces a derived
        // table containing member_id and older MySQL/Laravel combinations can turn
        // a constrained select into an ambiguous `member_id` query.  The Shares
        // management/profile pages may still use the relationship independently.
        $records = MembershipNewMember::with([
                'region:id,region_no,region',
                'membershipType:id,setting_value',
            ])
            ->forBusiness($businessId)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim((string) $request->search);
                $search = '%' . $term . '%';

                $q->where(function ($searchQuery) use ($search, $term) {
                    $searchQuery->where('member_code', 'like', $search)
                        ->orWhere('title', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('full_name', 'like', $search)
                        ->orWhere('full_name_second_language', 'like', $search)
                        ->orWhere('gender', 'like', $search)
                        ->orWhere('mobile', 'like', $search)
                        ->orWhere('other_mobile_nos', 'like', $search)
                        ->orWhere('business_name', 'like', $search)
                        ->orWhere('nic', 'like', $search)
                        ->orWhere('address', 'like', $search)
                        ->orWhereHas('region', function ($regionQuery) use ($search) {
                            $regionQuery->where('region', 'like', $search)
                                ->orWhere('region_no', 'like', $search);
                        })
                        ->orWhereHas('membershipType', function ($typeQuery) use ($search) {
                            $typeQuery->where('setting_value', 'like', $search);
                        });

                    if (strcasecmp($term, 'active') === 0) {
                        $searchQuery->orWhere('is_active', 1);
                    } elseif (strcasecmp($term, 'inactive') === 0) {
                        $searchQuery->orWhere('is_active', 0);
                    }
                });
            })
            ->latest('id')
            ->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))
            ->withQueryString();

        $currentBusinessName = $this->currentBusinessName();

        return view('membershipnew::members.index', compact('records', 'currentBusinessName'));
    }

    public function create()
    {
        return view('membershipnew::members.create', $this->formData());
    }

    public function store(Request $request, MembershipNewMemberService $service)
    {
        $businessId = MembershipNewContext::businessId();
        $data = $this->validatedMemberData($request, $businessId);
        $data['business_id'] = $businessId;
        $data['created_by'] = auth()->id();
        $data['is_active'] = $request->boolean('is_active');
        $service->create($data);

        return redirect()->route('membership-new.members.index')->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function show(MembershipNewMember $member, MembershipNewPointService $pointService)
    {
        abort_if((int) $member->business_id !== MembershipNewContext::businessId(), 403);
        $member->load(['activeCard', 'shareHolding', 'region', 'membershipType']);
        $pointBalance = $pointService->balance(MembershipNewContext::businessId(), $member->id);

        return view('membershipnew::members.show', compact('member', 'pointBalance'));
    }

    public function edit(MembershipNewMember $member)
    {
        abort_if((int) $member->business_id !== MembershipNewContext::businessId(), 403);

        return view('membershipnew::members.edit', array_merge(
            ['member' => $member],
            $this->formData($member)
        ));
    }

    public function update(Request $request, MembershipNewMember $member)
    {
        $businessId = MembershipNewContext::businessId();
        abort_if((int) $member->business_id !== $businessId, 403);

        $data = $this->validatedMemberData($request, $businessId);
        $data['updated_by'] = auth()->id();
        $data['is_active'] = $request->boolean('is_active');
        $member->update($data);

        return redirect()->route('membership-new.members.index')->with('status', __('membershipnew::messages.updated_successfully'));
    }

    public function destroy(MembershipNewMember $member)
    {
        abort_if((int) $member->business_id !== MembershipNewContext::businessId(), 403);
        $member->delete();

        return back()->with('status', __('membershipnew::messages.deleted_successfully'));
    }

    private function validatedMemberData(Request $request, int $businessId): array
    {
        $regionRule = Rule::exists('mn_regions', 'id')
            ->where(fn ($query) => $query->where('business_id', $businessId)->whereNull('deleted_at')->where('is_active', 1));

        $membershipTypeRule = Rule::exists('mn_setting_options', 'id')
            ->where(fn ($query) => $query
                ->where('business_id', $businessId)
                ->where('setting_group', 'membership_type')
                ->where('is_active', 1)
                ->whereNull('deleted_at'));

        $data = $request->validate([
            'member_code' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', Rule::in(self::TITLES)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'full_name_second_language' => ['nullable', 'string', 'max:255'],
            'region_id' => ['nullable', 'integer', $regionRule],
            'mobile' => ['nullable', 'string', 'max:30', 'regex:/^\+?[1-9][0-9]{6,14}$/'],
            'other_mobile_nos' => ['nullable', 'string', 'max:1000', 'regex:/^\s*\+?[1-9][0-9]{6,14}\s*(,\s*\+?[1-9][0-9]{6,14}\s*)*$/'],
            'business_name' => ['nullable', 'string', 'max:191'],
            'membership_type_id' => ['nullable', 'integer', $membershipTypeRule],
            'no_of_shares' => ['nullable', 'numeric', 'min:0', 'max:999999999999999999.9999'],
            'total_share_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999999999.9999'],
            'gender' => ['nullable', Rule::in(self::GENDERS)],
            'email' => ['nullable', 'email', 'max:255'],
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'joined_on' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'mobile.regex' => 'Mobile No must include the country code and must not start with 0. Example: 94771234567.',
            'other_mobile_nos.regex' => 'Enter Other Mobile Nos with country codes, without starting 0, separated by commas.',
        ]);

        $data['mobile'] = $this->cleanPhone($data['mobile'] ?? null);
        $data['other_mobile_nos'] = $this->cleanOtherPhones($data['other_mobile_nos'] ?? null);
        $data['business_name'] = isset($data['business_name']) ? trim((string) $data['business_name']) : null;

        return $data;
    }

    private function formData(?MembershipNewMember $member = null): array
    {
        $businessId = MembershipNewContext::businessId();

        $regions = Schema::hasTable('mn_regions')
            ? MembershipNewRegion::forBusiness($businessId)
                ->where('is_active', 1)
                ->select(['id', 'region_no', 'region'])
                ->orderBy('region')
                ->orderBy('region_no')
                ->get()
            : collect();

        $membershipTypes = Schema::hasTable('mn_setting_options')
            ? MembershipNewSettingOption::forBusiness($businessId)
                ->where('setting_group', 'membership_type')
                ->where('is_active', 1)
                ->select(['id', 'setting_value'])
                ->orderBy('setting_value')
                ->get()
            : collect();

        $currentBusinessName = $this->currentBusinessName();
        $businessNames = collect([$currentBusinessName, optional($member)->business_name])
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        return [
            'regions' => $regions,
            'membershipTypes' => $membershipTypes,
            'titles' => self::TITLES,
            'genders' => self::GENDERS,
            'businessNames' => $businessNames,
            'currencyLabel' => $this->currencyLabel(),
        ];
    }

    private function currentBusinessName(): string
    {
        foreach (['business.name', 'business.business_name', 'business_name'] as $key) {
            $value = session($key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function currencyLabel(): string
    {
        foreach ([
            'business.currency_symbol',
            'business.currency_code',
            'business.currency',
            'currency_symbol',
            'currency_code',
            'currency',
        ] as $key) {
            $value = session($key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return 'System Currency';
    }

    private function cleanPhone($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function cleanOtherPhones($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        return implode(', ', array_values(array_filter(array_map(
            fn ($phone) => trim((string) $phone),
            explode(',', $value)
        ))));
    }
}
