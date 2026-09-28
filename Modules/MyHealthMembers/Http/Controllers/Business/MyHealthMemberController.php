<?php

namespace Modules\MyHealthMembers\Http\Controllers\Business;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthMemberLogin;
use Modules\MyHealthMembers\Services\MyHealthMemberCodeService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;
use Modules\MyHealthMembers\Services\MyHealthQrService;

class MyHealthMemberController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(app(MyHealthPermissionService::class)->can('can_view_profile'), 403);

        $query = MyHealthMember::query()->with('login');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('myhealth_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nic_no', 'like', "%{$search}%")
                    ->orWhere('passport_no', 'like', "%{$search}%")
                    ->orWhereHas('login', function ($loginQuery) use ($search) {
                        $loginQuery->where('login_code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('blood_group')) {
            $query->where('blood_group', $request->input('blood_group'));
        }

        if ($request->filled('status') && $this->hasMemberColumn('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('registered_from')) {
            $query->whereDate('created_at', '>=', $request->input('registered_from'));
        }

        if ($request->filled('registered_to')) {
            $query->whereDate('created_at', '<=', $request->input('registered_to'));
        }

        $members = $query->orderByDesc('id')->paginate(25)->appends($request->query());

        $summary = [
            'total' => MyHealthMember::count(),
            'active' => $this->hasMemberColumn('status') ? MyHealthMember::where('status', 'active')->count() : MyHealthMember::where('is_active', 1)->count(),
            'today' => MyHealthMember::whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('myhealthmembers::members.index', compact('members', 'summary'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_register_member'), 403);

        return view('myhealthmembers::members.create', ['member' => new MyHealthMember()]);
    }

    public function store(Request $request, MyHealthMemberCodeService $codeService, MyHealthPermissionService $permissionService, MyHealthQrService $qrService)
    {
        abort_unless($permissionService->can('can_register_member'), 403);

        $data = $this->validatedData($request);
        $data['myhealth_code'] = $codeService->nextCode();
        $data['registered_source'] = 'business';
        $data['registered_business_id'] = $permissionService->businessId();
        $data['registered_by'] = auth()->id();
        $data['is_active'] = ($data['status'] ?? 'active') === 'active';

        $member = MyHealthMember::create($this->filterMemberData($data));
        $qrService->ensureToken($member);

        $loginCode = $codeService->nextLoginCode();

        MyHealthMemberLogin::create([
            'member_id' => $member->id,
            'login_code' => $loginCode,
            'password' => Hash::make(str()->random(16)),
        ]);

        return redirect()->route('myhealth.members.show', $member->id)
            ->with('status', __('myhealthmembers::lang.member_created_successfully') . ' Login Code: ' . $loginCode);
    }

    public function show(MyHealthMember $member, MyHealthQrService $qrService)
    {
        abort_unless(app(MyHealthPermissionService::class)->can('can_view_profile'), 403);

        $member->load([
            'login',
            'medicalHistory',
            'consultations' => fn ($q) => $q->latest('id')->limit(10),
            'diagnoses' => fn ($q) => $q->latest('id')->limit(10),
            'prescriptions' => fn ($q) => $q->latest('id')->limit(10),
            'labRequests' => fn ($q) => $q->latest('id')->limit(10),
            'documents' => fn ($q) => $q->latest('id')->limit(10),
            'auditLogs' => fn ($q) => $q->latest('id')->limit(10),
        ]);

        $qrUrl = $qrService->qrUrl($member);

        return view('myhealthmembers::members.show', compact('member', 'qrUrl'));
    }

    public function edit(MyHealthMember $member, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_edit_profile'), 403);

        return view('myhealthmembers::members.create', compact('member'));
    }

    public function update(Request $request, MyHealthMember $member, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_edit_profile'), 403);

        $data = $this->validatedData($request);
        $data['is_active'] = ($data['status'] ?? 'active') === 'active';

        $member->update($this->filterMemberData($data));

        return redirect()->route('myhealth.members.show', $member->id)
            ->with('status', 'My Health member updated successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'nic_no' => ['nullable', 'string', 'max:100'],
            'passport_no' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'blood_group' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_mobile' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_mobile' => ['nullable', 'string', 'max:30'],
            'height_feet' => ['nullable', 'integer', 'min:0', 'max:9'],
            'height_inches' => ['nullable', 'integer', 'min:0', 'max:11'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'status' => ['nullable', 'string', 'in:active,inactive,deceased'],
        ]);
    }

    private function filterMemberData(array $data): array
    {
        return collect($data)->filter(function ($value, $key) {
            return $this->hasMemberColumn($key);
        })->all();
    }

    private function hasMemberColumn(string $column): bool
    {
        return Schema::connection(config('myhealthmembers.central_connection'))->hasColumn('myhealth_members', $column);
    }
}
