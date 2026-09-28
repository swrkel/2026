<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Open SW shifts for the shared "SW Shift No" field used by core forms.
 *
 * Finance deposits, Customer payments, Expenses New and Purchase all use the
 * same Blade partial. Keep one authoritative feed here instead of duplicating
 * shift-status rules in those modules.
 */
class ShiftOptionController extends Controller
{
    public function index(Request $request)
    {
        $requestedLocationId = (int) $request->input('location_id', 0);
        $selectedShiftNo = trim((string) $request->input('selected_shift_no', ''));
        $keepSelected = filter_var($request->input('keep_selected', false), FILTER_VALIDATE_BOOLEAN);

        /*
         | This endpoint is also called from Finance AJAX modals. Resolve the
         | active tenant/business exactly from the current request and do not
         | depend on Schema metadata cached before tenant.context switched DBs.
         */
        $businessId = (int) (session('business.id')
            ?: session('user.business_id')
            ?: (optional(auth()->user())->business_id ?? 0));

        if ($businessId <= 0 || ! $this->liveTableExists('sw_shifts')) {
            return $this->noStoreJson([]);
        }

        $hasLocations = $this->liveTableExists('business_locations');
        $hasDeletedAt = $this->liveHasColumn('sw_shifts', 'deleted_at');
        $hasClosedAt = $this->liveHasColumn('sw_shifts', 'closed_at');

        /*
         | A host page can contain several #location_id elements (the main page
         | plus one or more AJAX modals). Never let an unrelated location hide
         | every valid shift. If the supplied location is not owned by this
         | business, fall back to the business-wide OPEN list.
         */
        $locationId = $requestedLocationId;
        if ($locationId > 0 && $hasLocations) {
            $belongs = DB::table('business_locations')
                ->where('id', $locationId)
                ->where('business_id', $businessId)
                ->exists();

            if (! $belongs) {
                $locationId = 0;
            }
        }

        /*
         | IS2263 - query OPEN rows directly from the live tenant DB.
         |
         | Previous builds first loaded the latest 500 shifts and filtered them
         | in PHP. Apart from doing unnecessary work, that could omit a valid
         | older open shift and depended on Schema::hasColumn(), whose metadata
         | can be stale after a tenant connection switch. This query has no such
         | limit and excludes a row immediately when it is closed.
         */
        $query = DB::table('sw_shifts as s')
            ->where('s.business_id', $businessId)
            ->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('0', 'open', 'opened')")
            ->when($hasClosedAt, fn ($q) => $q->whereNull('s.closed_at'))
            ->when($hasDeletedAt, fn ($q) => $q->whereNull('s.deleted_at'));

        if ($hasLocations) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 's.location_id');
        }

        $select = [
            's.id',
            's.sw_shift_no',
            's.shift_date',
            's.shift_name',
            's.location_id',
        ];
        $select[] = $hasLocations ? 'bl.name as location_name' : DB::raw('NULL as location_name');

        $allOpen = $query
            ->orderByDesc('s.shift_date')
            ->orderByDesc('s.id')
            ->get($select)
            ->values();

        $rows = $allOpen
            ->when($locationId > 0, fn ($items) => $items->filter(
                fn ($row) => (int) $row->location_id === $locationId
            ))
            ->values();

        /*
         | Keep the user's CURRENT selection across a harmless refresh only if
         | it is still OPEN in this business. A closed shift is not present in
         | $allOpen, so it disappears immediately from a new deposit/payment.
         | A genuine location change sends keep_selected=0.
         */
        if ($keepSelected && $selectedShiftNo !== ''
            && ! $rows->contains(fn ($row) => (string) $row->sw_shift_no === $selectedShiftNo)) {
            $selectedRow = $allOpen->first(
                fn ($row) => (string) $row->sw_shift_no === $selectedShiftNo
            );

            if ($selectedRow) {
                $rows->push($selectedRow);
            }
        }

        $payload = $rows
            ->unique(fn ($row) => (string) $row->sw_shift_no)
            ->map(function ($row) use ($locationId) {
                $label = (string) $row->sw_shift_no;

                // Identify location whenever this is a business-wide list, or
                // when a preserved selection belongs to another location.
                if (($locationId <= 0 || (int) $row->location_id !== $locationId)
                    && ! empty($row->location_name)) {
                    $label .= '  ·  ' . $row->location_name;
                }

                if (! empty($row->shift_date)) {
                    $label .= '  ·  ' . \Carbon\Carbon::parse($row->shift_date)->format('d/m/Y');
                }

                if (! empty($row->shift_name)) {
                    $label .= '  ·  ' . $row->shift_name;
                }

                return [
                    'id' => (int) $row->id,
                    'no' => (string) $row->sw_shift_no,
                    'label' => $label,
                    'location_id' => (int) $row->location_id,
                ];
            })
            ->values()
            ->all();

        return $this->noStoreJson($payload);
    }

    /**
     * Check the database selected for THIS request, not Schema facade metadata
     * that may have been created before tenant.context changed connections.
     */
    protected function liveTableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT 1 FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    protected function liveHasColumn(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Never cache an open/closed shift list. */
    protected function noStoreJson(array $payload)
    {
        return response()->json($payload)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
