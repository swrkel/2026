<?php

namespace Modules\ChurchManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ChurchManagement\Http\Controllers\Concerns\ChurchTenantContext;

/**
 * Events — anything in the church calendar that is not a regular service.
 */
class EventController extends Controller
{
    use ChurchTenantContext;

    public const STATUSES = [
        'planned'   => 'Planned',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function index(Request $request)
    {
        $installed = $this->tableExists('events');

        $events = collect();
        $members = collect();
        $counts = ['upcoming' => 0, 'this_month' => 0];

        if ($installed) {
            $query = $this->scopedQuery('events');

            /*
             | Default view is UPCOMING, not everything.
             |
             | A church calendar is consulted to find out what is next far more
             | often than to browse what has already happened. "All" and "Past"
             | remain a click away.
             */
            $scope = $request->input('scope', 'upcoming');

            if ($scope === 'upcoming') {
                $query->whereDate('event_date', '>=', now()->format('Y-m-d'));
            } elseif ($scope === 'past') {
                $query->whereDate('event_date', '<', now()->format('Y-m-d'));
            }

            $status = $request->input('status');
            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            if ($search = trim((string) $request->input('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('venue', 'like', '%' . $search . '%')
                        ->orWhere('event_type', 'like', '%' . $search . '%');
                });
            }

            // Upcoming ascending (what is next first); past descending (most
            // recent first). Either other way round buries what matters.
            $events = $query
                ->orderBy('event_date', $scope === 'past' ? 'desc' : 'asc')
                ->orderBy('id', 'desc')
                ->paginate(25)
                ->withQueryString();

            $counts['upcoming'] = (int) $this->scopedQuery('events')
                ->whereDate('event_date', '>=', now()->format('Y-m-d'))
                ->whereIn('status', ['planned', 'confirmed'])
                ->count();

            $counts['this_month'] = (int) $this->scopedQuery('events')
                ->whereBetween('event_date', [
                    now()->startOfMonth()->format('Y-m-d'),
                    now()->endOfMonth()->format('Y-m-d'),
                ])->count();

            $members = $this->tableExists('members')
                ? $this->scopedQuery('members')->orderBy('full_name')->pluck('full_name', 'id')
                : collect();

            $request->merge(['scope' => $scope]);
        }

        return view('churchmanagement::events.index', [
            'installed' => $installed,
            'events'    => $events,
            'members'   => $members,
            'statuses'  => self::STATUSES,
            'counts'    => $counts,
            'locations' => $this->locationOptions(),
            'filters'   => $request->only(['scope', 'status', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->tableExists('events')) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        $data = $this->validated($request);

        try {
            $data = $this->withScope($data);
            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::table($this->table('events'))->insert($data);

            return back()->with('chc_success', 'Event added successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: event create failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to save this event.');
        }
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validated($request);

        try {
            $data['updated_by'] = Auth::id();

            if (! $this->updateScopedRow('events', $id, $data)) {
                return back()->with('chc_error', 'That event was not found for this business.');
            }

            return back()->with('chc_success', 'Event updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: event update failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to update this event.');
        }
    }

    public function destroy(int $id)
    {
        try {
            if (! $this->deleteScopedRow('events', $id)) {
                return back()->with('chc_error', 'That event was not found for this business.');
            }

            return back()->with('chc_success', 'Event removed.');
        } catch (\Throwable $e) {
            Log::error('Church Management: event delete failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->with('chc_error', 'Unable to remove this event.');
        }
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title'                => 'required|string|max:191',
            'event_type'           => 'nullable|string|max:50',
            'event_date'           => 'required|date',
            // after_or_equal, not after: a multi-day event may start and end on
            // the same day, and rejecting that would be wrong.
            'end_date'             => 'nullable|date|after_or_equal:event_date',
            'start_time'           => 'nullable',
            'end_time'             => 'nullable',
            'venue'                => 'nullable|string|max:191',
            'organiser_member_id'  => 'nullable|integer',
            'business_location_id' => 'nullable|integer',
            'status'               => 'nullable|in:' . implode(',', array_keys(self::STATUSES)),
            'description'          => 'nullable|string|max:2000',
        ], [
            'title.required'            => 'Give the event a title.',
            'event_date.required'       => 'Enter the date of the event.',
            'end_date.after_or_equal'   => 'The end date cannot be before the start date.',
        ]);

        foreach (['end_date', 'start_time', 'end_time', 'organiser_member_id', 'business_location_id'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = null;
            }
        }

        $data['status'] = $data['status'] ?? 'planned';

        return $data;
    }
}
