<?php

namespace Modules\MPCS\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Modules\MPCS\Services\F15DailyReportService;

class F15DailyReportController extends Controller
{
    private $reportService;

    public function __construct(F15DailyReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $businessId = $this->businessId($request);
        abort_if($businessId <= 0, 403, 'Business context is missing.');

        $locations = $this->permittedLocations($businessId);
        abort_if($locations->isEmpty(), 403, 'No permitted business location is available.');

        $selectedLocationId = (int) $request->input('location_id', $locations->keys()->first());
        if (! $locations->has($selectedLocationId)) {
            $selectedLocationId = (int) $locations->keys()->first();
        }

        $business = Business::find($businessId);
        $currencyPrecision = (int) ($business->currency_precision ?? 2);
        $selectedDate = $this->date($request->input('date', now()->toDateString()));

        return view('mpcs::forms.F15_daily_report', compact(
            'locations',
            'selectedLocationId',
            'selectedDate',
            'currencyPrecision'
        ));
    }

    public function data(Request $request)
    {
        $businessId = $this->businessId($request);
        abort_if($businessId <= 0, 403, 'Business context is missing.');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                }),
            ],
        ]);

        $locationId = (int) $validated['location_id'];
        $this->authorizeLocation($businessId, $locationId);

        $report = $this->reportService->build(
            $businessId,
            $locationId,
            $this->date($validated['date'])
        );

        $location = BusinessLocation::where('business_id', $businessId)->findOrFail($locationId);
        $report['location_name'] = $location->name;

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * IS2029: a DEDICATED print page.
     *
     * The report used to be printed from the working screen with @media print
     * rules that hid the theme around it. That never became reliable: the tab
     * strip, the sidebar toggle and the floating widgets kept appearing, and the
     * attempt to hide the page wholesale and re-show only the report printed a
     * blank sheet - the same failure recorded under IS2009.
     *
     * The cause is structural, not a missing selector. The report sits several
     * levels inside a shared layout, so printing it well means suppressing an
     * unknown and changing set of ancestors and siblings.
     *
     * This renders the report on its own standalone page instead - no theme, no
     * tabs, no sidebar, nothing to hide. What is printed is exactly what the
     * view contains. The same approach already fixed the F10 receipt print
     * under IS2026.
     *
     * It reuses build() from the service, so the printed figures are the same
     * ones the screen shows; there is no second calculation to drift.
     */
    public function print(Request $request)
    {
        $businessId = $this->businessId($request);
        abort_if($businessId <= 0, 403, 'Business context is missing.');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                }),
            ],
        ]);

        $locationId = (int) $validated['location_id'];
        $this->authorizeLocation($businessId, $locationId);

        $report = $this->reportService->build(
            $businessId,
            $locationId,
            $this->date($validated['date'])
        );

        $location = BusinessLocation::where('business_id', $businessId)->findOrFail($locationId);
        $report['location_name'] = $location->name;

        return view('mpcs::forms.partials.f15_daily_report_print', [
            'report' => $report,
        ]);
    }

    public function save(Request $request)
    {
        $businessId = $this->businessId($request);
        abort_if($businessId <= 0, 403, 'Business context is missing.');

        if (! Schema::hasTable('mpcs_f15_daily_reports')) {
            return response()->json([
                'success' => false,
                'msg' => 'The F15 Daily Report database table is not available. Run the supplied migration or raw SQL in this tenant database first.',
            ], 422);
        }

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where(function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                }),
            ],
            'changes_addition' => ['nullable', 'numeric'],
            'changes_deduction' => ['nullable', 'numeric'],
            'damaged' => ['nullable', 'numeric'],
            'others' => ['nullable', 'numeric'],
            'total_return' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'prepared_by' => ['nullable', 'string', 'max:191'],
            'prepared_date' => ['nullable', 'date'],
            'checked_by' => ['nullable', 'string', 'max:191'],
            'checked_date' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'string', 'max:191'],
            'approved_date' => ['nullable', 'date'],
        ]);

        $locationId = (int) $validated['location_id'];
        $this->authorizeLocation($businessId, $locationId);

        $report = $this->reportService->save(
            $businessId,
            $locationId,
            $this->date($validated['date']),
            $validated,
            (int) auth()->id()
        );

        $location = BusinessLocation::where('business_id', $businessId)->findOrFail($locationId);
        $report['location_name'] = $location->name;

        return response()->json([
            'success' => true,
            'msg' => 'F15 Daily Report saved successfully.',
            'data' => $report,
        ]);
    }

    private function businessId(Request $request): int
    {
        return (int) (
            $request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: optional(auth()->user())->business_id
        );
    }

    private function permittedLocations(int $businessId)
    {
        $query = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->where('is_active', 1)
            ->orderBy('name');

        $permitted = auth()->user()->permitted_locations();
        if ($permitted !== 'all') {
            $query->whereIn('id', array_map('intval', (array) $permitted));
        }

        return $query->pluck('name', 'id');
    }

    private function authorizeLocation(int $businessId, int $locationId): void
    {
        $exists = BusinessLocation::query()
            ->where('business_id', $businessId)
            ->where('id', $locationId)
            ->where('is_active', 1)
            ->exists();
        abort_unless($exists, 403, 'Business location is not available.');

        $permitted = auth()->user()->permitted_locations();
        if ($permitted !== 'all') {
            abort_unless(
                in_array($locationId, array_map('intval', (array) $permitted), true),
                403,
                'You do not have access to this business location.'
            );
        }
    }

    private function date($value): string
    {
        return Carbon::parse($value)->toDateString();
    }
}
