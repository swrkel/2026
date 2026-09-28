<?php

namespace Modules\ChurchManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ChurchManagement\Http\Controllers\Concerns\ChurchTenantContext;

/**
 * Members — the congregation roll.
 */
class MemberController extends Controller
{
    use ChurchTenantContext;

    /**
     * Statuses offered on the form and in the filter.
     *
     * Declared once here rather than repeated in the views and the validation
     * rules, which is how a list and its validator drift apart.
     */
    public const STATUSES = [
        'member'   => 'Member',
        'visitor'  => 'Visitor',
        'inactive' => 'Inactive',
        'departed' => 'Departed',
    ];

    public const GENDERS = [
        'male'   => 'Male',
        'female' => 'Female',
        'other'  => 'Other',
    ];

    public const MARITAL_STATUSES = [
        'single'   => 'Single',
        'married'  => 'Married',
        'widowed'  => 'Widowed',
        'divorced' => 'Divorced',
        'other'    => 'Other',
    ];

    public function index(Request $request)
    {
        $installed = $this->moduleInstalled();

        $members = collect();
        $families = collect();

        if ($installed) {
            $query = $this->scopedQuery('members');

            /*
             | Filters are applied only when they carry a value, so an untouched
             | filter bar returns everything rather than nothing.
             */
            if ($search = trim((string) $request->input('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('member_code', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            $status = $request->input('status');
            if ($status !== null && $status !== '') {
                $query->where('membership_status', $status);
            }

            if ($familyId = $request->input('family_id')) {
                $query->where('family_id', (int) $familyId);
            }

            $members = $query->orderBy('full_name')->paginate(25)->withQueryString();

            $families = $this->scopedQuery('families')
                ->orderBy('family_name')
                ->pluck('family_name', 'id');
        }

        /*
         | Locations and creator names come from the shared core tables
         | (business_locations, users), which this ticket confirmed the module
         | may use. Both are fetched for the whole page in one query each - a
         | lookup per row would be an N+1 that grows with the roll.
         */
        $locations = $this->locationOptions();

        $creators = $installed
            ? $this->userNames(collect($members->items())->pluck('created_by')->all())
            : collect();

        return view('churchmanagement::members.index', [
            'installed' => $installed,
            'members'   => $members,
            'families'  => $families,
            'locations' => $locations,
            'creators'  => $creators,
            'statuses'  => self::STATUSES,
            'genders'   => self::GENDERS,
            'maritals'  => self::MARITAL_STATUSES,
            'filters'   => $request->only(['search', 'status', 'family_id']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if (! $this->moduleInstalled()) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        try {
            $data = $this->withScope($data);

            // Generated rather than accepted from the form: a posted code could
            // duplicate an existing member's.
            $data['member_code'] = $this->nextCode(
                'members',
                'member_code',
                (string) config('churchmanagement.member_code_prefix', 'CM')
            );

            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::table($this->table('members'))->insert($data);

            return back()->with('chc_success', 'Member added successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: member create failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to save this member. Please try again.');
        }
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validated($request);

        try {
            $data['updated_by'] = Auth::id();

            // business_id is deliberately NOT in $data here: an update must
            // never be able to move a row to another business.
            $updated = $this->updateScopedRow('members', $id, $data);

            if (! $updated) {
                return back()->with('chc_error', 'That member was not found for this business.');
            }

            return back()->with('chc_success', 'Member updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: member update failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to update this member.');
        }
    }

    public function destroy(int $id)
    {
        try {
            /*
             | Soft delete. The roll is a historical record, and a member who
             | leaves may return - keeping the row means their history comes
             | back with them.
             */
            $deleted = $this->deleteScopedRow('members', $id);

            if (! $deleted) {
                return back()->with('chc_error', 'That member was not found for this business.');
            }

            return back()->with('chc_success', 'Member removed.');
        } catch (\Throwable $e) {
            Log::error('Church Management: member delete failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->with('chc_error', 'Unable to remove this member.');
        }
    }

    /**
     * Validate and normalise a submitted member.
     *
     * Shared by store and update so the two cannot diverge.
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'nullable|string|max:100',
            'family_id'         => 'nullable|integer',
            'business_location_id' => 'nullable|integer',
            'family_role'       => 'nullable|string|max:50',
            'gender'            => 'nullable|in:' . implode(',', array_keys(self::GENDERS)),
            'date_of_birth'     => 'nullable|date',
            'marital_status'    => 'nullable|in:' . implode(',', array_keys(self::MARITAL_STATUSES)),
            'phone'             => 'nullable|string|max:50',
            'whatsapp'          => 'nullable|string|max:50',
            'email'             => 'nullable|email|max:191',
            'address'           => 'nullable|string|max:1000',
            'city'              => 'nullable|string|max:100',
            'joined_date'       => 'nullable|date',
            'baptism_date'      => 'nullable|date',
            'confirmation_date' => 'nullable|date',
            'membership_status' => 'nullable|in:' . implode(',', array_keys(self::STATUSES)),
            'occupation'        => 'nullable|string|max:191',
            'notes'             => 'nullable|string|max:2000',
        ], [
            'first_name.required' => 'A first name is required.',
            'email.email'         => 'Enter a valid email address.',
        ]);

        $data['first_name'] = trim($data['first_name']);
        $data['last_name'] = trim((string) ($data['last_name'] ?? ''));

        /*
         | full_name is stored, not derived on read, so the list can sort and
         | search on one indexed column instead of a CONCAT that no index can
         | serve. It is rebuilt here on every write so it cannot drift from the
         | two parts it comes from.
         */
        $data['full_name'] = trim($data['first_name'] . ' ' . $data['last_name']);

        $data['membership_status'] = $data['membership_status'] ?? 'member';

        // A family of 0 from an unselected dropdown means "no family", not
        // family number zero.
        if (empty($data['family_id'])) {
            $data['family_id'] = null;
        }

        // An unselected location dropdown posts an empty string; that means
        // "no location", not location zero.
        if (empty($data['business_location_id'])) {
            $data['business_location_id'] = null;
        }

        return $data;
    }
}
