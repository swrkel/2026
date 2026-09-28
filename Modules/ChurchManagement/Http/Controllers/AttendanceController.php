<?php

namespace Modules\ChurchManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ChurchManagement\Http\Controllers\Concerns\ChurchTenantContext;

/**
 * Attendance — services, and the register taken against them.
 *
 * A congregation records attendance one of two ways, and this supports both:
 *
 *   HEADCOUNT  a number on the service itself, for a room that is counted
 *              rather than registered.
 *   REGISTER   a row per member, for congregations that track who came.
 *
 * Neither is imposed. A service can carry a headcount, a register, or both.
 */
class AttendanceController extends Controller
{
    use ChurchTenantContext;

    public const STATUSES = [
        'present' => 'Present',
        'absent'  => 'Absent',
        'excused' => 'Excused',
    ];

    public function index(Request $request)
    {
        $installed = $this->tableExists('services');

        $services = collect();
        $selected = null;
        $register = collect();
        $members = collect();
        $summary = ['present' => 0, 'absent' => 0, 'excused' => 0, 'unmarked' => 0];

        if ($installed) {
            $query = $this->scopedQuery('services');

            $from = $request->input('from') ?: now()->subMonths(3)->format('Y-m-d');
            $to   = $request->input('to') ?: now()->format('Y-m-d');
            $query->whereBetween('service_date', [$from, $to]);

            $services = $query->orderBy('service_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(15)
                ->withQueryString();

            /*
             | Attendance counts for every service on the page in ONE query.
             | Counting inside the row loop would be an N+1 that grows with the
             | number of services shown.
             */
            $serviceIds = collect($services->items())->pluck('id')->all();

            $marked = empty($serviceIds) || ! $this->tableExists('attendance')
                ? collect()
                : $this->scopedQuery('attendance')
                    ->whereIn('service_id', $serviceIds)
                    ->where('status', 'present')
                    ->select('service_id', DB::raw('COUNT(*) as present_count'))
                    ->groupBy('service_id')
                    ->pluck('present_count', 'service_id');

            foreach ($services as $service) {
                $service->present_count = (int) ($marked[$service->id] ?? 0);
            }

            $members = $this->tableExists('members')
                ? $this->scopedQuery('members')
                    ->where('membership_status', 'member')
                    ->orderBy('full_name')
                    ->get(['id', 'full_name', 'member_code'])
                : collect();

            // A service opened for its register.
            if ($serviceId = (int) $request->input('service_id')) {
                $selected = $this->scopedQuery('services')->where('id', $serviceId)->first();

                if ($selected && $this->tableExists('attendance')) {
                    $register = $this->scopedQuery('attendance')
                        ->where('service_id', $selected->id)
                        ->pluck('status', 'member_id');

                    foreach ($members as $member) {
                        $status = $register[$member->id] ?? null;
                        if ($status === null) {
                            $summary['unmarked']++;
                        } else {
                            $summary[$status] = ($summary[$status] ?? 0) + 1;
                        }
                    }
                }
            }

            $request->merge(['from' => $from, 'to' => $to]);
        }

        return view('churchmanagement::attendance.index', [
            'installed' => $installed,
            'services'  => $services,
            'selected'  => $selected,
            'register'  => $register,
            'members'   => $members,
            'summary'   => $summary,
            'statuses'  => self::STATUSES,
            'locations' => $this->locationOptions(),
            'filters'   => $request->only(['from', 'to', 'service_id']),
        ]);
    }

    public function storeService(Request $request)
    {
        if (! $this->tableExists('services')) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        $data = $this->validatedService($request);

        try {
            $data = $this->withScope($data);
            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $data['updated_at'] = now();

            $id = DB::table($this->table('services'))->insertGetId($data);

            // Straight into the new service's register, which is what the user
            // wants next in almost every case.
            return redirect()
                ->route('churchmanagement.attendance.index', ['service_id' => $id])
                ->with('chc_success', 'Service added. Mark the register below.');
        } catch (\Throwable $e) {
            Log::error('Church Management: service create failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to add this service.');
        }
    }

    public function updateService(Request $request, int $id)
    {
        $data = $this->validatedService($request);

        try {
            if (! $this->updateScopedRow('services', $id, $data)) {
                return back()->with('chc_error', 'That service was not found for this business.');
            }

            return back()->with('chc_success', 'Service updated.');
        } catch (\Throwable $e) {
            Log::error('Church Management: service update failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to update this service.');
        }
    }

    public function destroyService(int $id)
    {
        try {
            /*
             | The register goes with the service.
             |
             | chc_attendance has no deleted_at - an attendance row is a fact
             | about a service, not a record with a life of its own. Leaving the
             | rows behind would orphan them against a service nobody can open.
             */
            if ($this->tableExists('attendance')) {
                $this->scopedQuery('attendance')->where('service_id', $id)->delete();
            }

            if (! $this->deleteScopedRow('services', $id)) {
                return back()->with('chc_error', 'That service was not found for this business.');
            }

            return back()->with('chc_success', 'Service and its register removed.');
        } catch (\Throwable $e) {
            Log::error('Church Management: service delete failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->with('chc_error', 'Unable to remove this service.');
        }
    }

    /**
     * Save a whole register in one submission.
     */
    public function saveRegister(Request $request, int $serviceId)
    {
        if (! $this->tableExists('attendance')) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        $service = $this->scopedQuery('services')->where('id', $serviceId)->first();

        if (! $service) {
            return back()->with('chc_error', 'That service was not found for this business.');
        }

        $marks = (array) $request->input('attendance', []);

        try {
            $table = $this->table('attendance');
            $businessId = $this->businessId();
            $userId = Auth::id();

            DB::transaction(function () use ($marks, $table, $businessId, $userId, $serviceId) {
                foreach ($marks as $memberId => $status) {
                    $memberId = (int) $memberId;

                    if (! $memberId) {
                        continue;
                    }

                    /*
                     | An unmarked member is recorded as nothing at all, not as
                     | absent. "We did not take their name" and "they were not
                     | there" are different facts, and conflating them would
                     | overstate absence for any congregation that only marks
                     | who arrived.
                     */
                    if (! array_key_exists($status, self::STATUSES)) {
                        DB::table($table)
                            ->where('service_id', $serviceId)
                            ->where('member_id', $memberId)
                            ->delete();
                        continue;
                    }

                    /*
                     | updateOrInsert against the (service_id, member_id) unique
                     | key, so submitting the register twice corrects the rows
                     | rather than duplicating them.
                     */
                    DB::table($table)->updateOrInsert(
                        ['service_id' => $serviceId, 'member_id' => $memberId],
                        [
                            'business_id' => $businessId,
                            'status'      => $status,
                            'created_by'  => $userId,
                            'updated_at'  => now(),
                            'created_at'  => now(),
                        ]
                    );
                }
            });

            return back()->with('chc_success', 'Register saved.');
        } catch (\Throwable $e) {
            Log::error('Church Management: register save failed', [
                'service_id' => $serviceId,
                'message'    => $e->getMessage(),
            ]);

            return back()->with('chc_error', 'Unable to save the register.');
        }
    }

    protected function validatedService(Request $request): array
    {
        $data = $request->validate([
            'title'                => 'required|string|max:191',
            'service_date'         => 'required|date',
            'service_time'         => 'nullable',
            'service_type'         => 'nullable|string|max:50',
            'headcount'            => 'nullable|integer|min:0',
            'business_location_id' => 'nullable|integer',
            'notes'                => 'nullable|string|max:2000',
        ], [
            'title.required'        => 'Give the service a title.',
            'service_date.required' => 'Enter the date of the service.',
        ]);

        if (empty($data['business_location_id'])) {
            $data['business_location_id'] = null;
        }

        // An empty headcount box means "not counted", not zero attended.
        if ($data['headcount'] === '' || $data['headcount'] === null) {
            $data['headcount'] = null;
        }

        if (empty($data['service_time'])) {
            $data['service_time'] = null;
        }

        return $data;
    }
}
