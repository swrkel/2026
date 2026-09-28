<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\NumberService;
use Modules\SW\Services\SettlementBuilderService;
use Modules\SW\Services\SettlementSaveService;
use Modules\SW\Services\ExpenseCategoryLookupService;

/**
 * SW Settlement — create.  8043.
 *
 * Header: settlement number, location, operator, date, shift, note.
 *
 * The operator is chosen before the shifts, and the shift list follows from it:
 * 8043 says shifts are linked to the selected operator only.
 */
class SettlementCreateController extends Controller
{
    public function __construct(
        protected SettlementBuilderService $builder,
        protected SettlementSaveService $saver,
        protected NumberService $numbers,
        protected ExpenseCategoryLookupService $expenseCategoryLookup
    ) {
    }

    protected function businessId(): int
    {
        // tenant.context runs before auth for SW routes. Prefer the authenticated
        // TENANT user over any session object that may have been created on a
        // different host/database in an earlier request.
        $userBusinessId = (int) (optional(auth()->user())->business_id ?? 0);

        return $userBusinessId > 0
            ? $userBusinessId
            : (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function create()
    {
        $businessId = $this->businessId();

        abort_unless(
            auth()->user()->can('superadmin') || auth()->user()->can('sw.settlement.create'),
            403
        );

        $locations = $this->locations($businessId);
        $defaultLocation = $locations->count() === 1 ? (int) $locations->keys()->first() : null;

        /*
         | IS2231.
         |
         | A SW settlement can only consume CLOSED, not-yet-settled shifts,
         | while IS2230 also asked the operator list to be limited to people
         | who currently have an OPEN shift.  Applying only the OPEN test made
         | this dropdown unusable on installations whose shift status is stored
         | in the older textual form, and it also hid an operator immediately
         | after their shift was closed for settlement.
         |
         | Keep the list relevant but usable: show operators who are working on
         | an OPEN shift OR have a CLOSED pending shift ready to settle, grouped
         | by location.  The actual shift picker below still permits CLOSED,
         | not-yet-settled shifts only.
        */
        $operatorsByLocation = $this->settlementOperatorsByLocation(
            $businessId,
            $locations->keys()->map(fn ($id) => (int) $id)->all()
        );

        $defaultOperators = collect();
        if ($defaultLocation) {
            $defaultOperators = collect($operatorsByLocation[(string) $defaultLocation] ?? [])
                ->pluck('name', 'id');
        }

        return view('sw::settlements.create', [
            'business_locations' => $locations,

            // One location: choose it. Making someone pick from a list of one
            // is a question with only one answer.
            'default_location' => $defaultLocation,

            'pump_operators' => $defaultOperators,
            'settlement_operators_by_location' => $operatorsByLocation,

            /*
             | Shown before anything is saved, so the user knows which number
             | they are working on.
             |
             | peek() does not consume it - the real number is taken at save
             | time, so an abandoned settlement leaves no gap in the sequence
             | and two people starting at once do not both hold the same one.
            */
            'settlement_no' => $this->numbers->peek(
                $businessId,
                (int) ($locations->count() === 1 ? $locations->keys()->first() : 0),
                'settlement'
            ),

            // The picker starts after the later of the business opening date
            // and the accounts-closed-through date.
            'opening_date' => $this->minimumSettlementDate($businessId),
            'max_date' => $this->maxDate($businessId),

            // IS2230: Expenses on the settlement need a category selector.
            // No schema change is required; the chosen category is preserved in
            // the collection reference and the selected Finance account remains
            // the posting account.
            'expense_categories' => $this->expenseCategories($businessId),

            // IS2232: use the business's configured date format end-to-end.
            // Hard-coding d/m/Y made an m/d/Y date such as 09/10/2026 parse
            // as 9 October instead of 10 September.
            'settlement_date_php_format' => $this->settlementDateFormat($businessId),
            'settlement_date_picker_format' => $this->datepickerDateFormat($businessId),
        ]);
    }

    /**
     * Pending shifts for one operator at one location.
     *
     * CLOSED and not already settled. An open shift is still taking money;
     * a settled one has been through this already.
     */
    public function availableShifts(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = (int) $request->input('location_id');
        $operatorId = (int) $request->input('pump_operator_id');

        if ($businessId <= 0 || $locationId <= 0 || $operatorId <= 0) {
            return response()->json([]);
        }

        $rows = Shift::where('sw_shifts.business_id', $businessId)
            ->where('sw_shifts.location_id', $locationId)
            // Numeric 1 is the current schema; 'closed' is kept for older
            // SW databases that pre-date the tinyint status migration. CAST to
            // CHAR avoids MySQL coercing arbitrary text statuses to zero.
            ->where(function ($statusQuery) {
                $statusQuery
                    ->whereRaw("LOWER(TRIM(CAST(sw_shifts.status AS CHAR))) IN ('1', 'closed', 'close')");

                if (Schema::hasColumn('sw_shifts', 'closed_at')) {
                    $statusQuery->orWhere(function ($legacy) {
                        $legacy
                            ->whereRaw("LOWER(TRIM(CAST(sw_shifts.status AS CHAR))) IN ('0', 'open', 'opened')")
                            ->whereNotNull('sw_shifts.closed_at');
                    });
                }
            })
            ->when(
                Schema::hasColumn('sw_shifts', 'deleted_at'),
                fn ($q) => $q->whereNull('sw_shifts.deleted_at')
            )
            // Linked to the operator - 8043.
            ->whereExists(function ($q) use ($operatorId) {
                $q->select(DB::raw(1))
                  ->from('sw_shift_operators')
                  ->whereColumn('sw_shift_operators.sw_shift_id', 'sw_shifts.id')
                  ->where('sw_shift_operators.pump_operator_id', $operatorId);
            })
            ->when(Schema::hasTable('sw_settlement_shifts'), function ($q) {
                $q->whereNotExists(function ($settled) {
                    $settled->select(DB::raw(1))
                        ->from('sw_settlement_shifts')
                        ->whereColumn('sw_settlement_shifts.sw_shift_id', 'sw_shifts.id');
                });
            })
            ->orderBy('sw_shifts.shift_date')
            ->orderBy('sw_shifts.id')
            ->limit(100)
            ->get(['sw_shifts.id', 'sw_shifts.sw_shift_no', 'sw_shifts.shift_date', 'sw_shifts.shift_name']);

        return response()->json($rows->map(function ($r) {
            $label = $r->sw_shift_no;

            if ($r->shift_date) {
                $label .= '  ·  ' . \Carbon\Carbon::parse($r->shift_date)->format('d/m/Y');
            }

            if (! empty($r->shift_name)) {
                $label .= '  ·  ' . $r->shift_name;
            }

            return ['id' => $r->id, 'label' => $label];
        }));
    }

    /** What the daily tabs recorded against the chosen shifts. */
    public function assemble(Request $request)
    {
        $businessId = $this->businessId();

        $shiftIds = array_filter(array_map('intval', (array) $request->input('shift_ids', [])));

        if (empty($shiftIds)) {
            return response('');
        }

        return view('sw::settlements.partials.settlement_body', [
            'bundle' => $this->builder->assemble($businessId, $shiftIds),
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $this->businessId();

        abort_unless(
            auth()->user()->can('superadmin') || auth()->user()->can('sw.settlement.create'),
            403
        );

        $data = $request->validate([
            'location_id' => 'required|integer',
            'pump_operator_id' => 'required|integer',
            /*
             | Optional here, checked below.
             |
             | A site with no pending shifts must still settle. Where pending
             | shifts exist one has to be chosen - which "required" cannot
             | express, since it depends on whether there are any.
            */
            'shift_ids' => 'nullable|array',
            'shift_ids.*' => 'integer',
            'transaction_date' => 'required|string|max:20',
            'note' => 'nullable|string',
            'lines' => 'nullable|array',

            /*
             | The other four sections.
             |
             | Absent from the rules, validate() strips them and the settlement
             | saves with four sections silently missing - the user watching it
             | work and losing the lot.
            */
            'other_sales' => 'nullable|array',
            'other_income' => 'nullable|array',
            'credit_sales' => 'nullable|array',
            'payments' => 'nullable|array',

            'total_meter_sales' => 'nullable|numeric',
            'total_other_sales' => 'nullable|numeric',
            'total_other_income' => 'nullable|numeric',
            'total_credit_sales' => 'nullable|numeric',
        ]);

        // The screen uses dd/mm/yyyy, while the database and opening-date
        // controls use ISO yyyy-mm-dd.  Comparing the raw display string with
        // an ISO opening date made values such as 06/09/2026 look "less than"
        // 2025-05-02 lexicographically and raised the false 422 in IS2204.
        $data['transaction_date'] = $this->normaliseSettlementDate($data['transaction_date']);

        $this->assertDateAllowed($businessId, $data['transaction_date']);

        /*
         | A pending shift may not be stepped around.
         |
         | Checked on the server as well as in the browser - a settlement that
         | skips an outstanding shift would leave that shift's cash unaccounted
         | for, and the browser check can be bypassed.
        */
        $this->assertNoPendingShiftSkipped(
            $businessId,
            (int) $data['location_id'],
            (int) $data['pump_operator_id'],
            $data['shift_ids'] ?? []
        );

        try {
            $settlementId = $this->saver->create($businessId, $data);
        } catch (\RuntimeException $e) {
            /*
             | IS2245: a controlled Finance mapping refusal must not replace the
             | settlement form with a Symfony error page. SettlementSaveService
             | is transactional, so nothing partial is committed. Return the
             | exact reason and keep the submitted values/draft available.
            */
            Log::warning('SW settlement save refused by Finance posting.', [
                'business_id' => $businessId,
                'location_id' => (int) ($data['location_id'] ?? 0),
                'pump_operator_id' => (int) ($data['pump_operator_id'] ?? 0),
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        } catch (\Throwable $e) {
            /*
             | S753: an unexpected database/schema exception must return control
             | to the form instead of leaving the operator with a permanently
             | disabled "Saving..." button or a generic server error page.
             | SettlementSaveService is transactional, so the failed attempt is
             | rolled back before this response is created.
             */
            Log::error('SW settlement save failed.', [
                'business_id' => $businessId,
                'location_id' => (int) ($data['location_id'] ?? 0),
                'pump_operator_id' => (int) ($data['pump_operator_id'] ?? 0),
                'shift_ids' => array_values(array_map('intval', (array) ($data['shift_ids'] ?? []))),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('sw::lang.unable_to_save_settlement'),
                ]);
        }

        $settlementNo = DB::table('sw_settlements')->where('id', $settlementId)->value('settlement_no');

        // Match the Petro PD / Petro Direct confirmation workflow: after
        // the user confirms the Settlement Preview, save the authoritative SW
        // snapshot and immediately show the printable preview of that exact
        // saved settlement. Use a relative route so the tenant host cannot be
        // changed by APP_URL.
        return redirect()->to(route('sw.settlements.print', ['id' => $settlementId], false))
            ->with('status', [
                'success' => 1,
                'msg' => __('sw::lang.settlement_created', ['number' => $settlementNo]),
            ]);
    }

    /** Refuse when pending shifts exist and none was chosen. */
    protected function assertNoPendingShiftSkipped(
        int $businessId, int $locationId, int $operatorId, array $chosen
    ): void {
        if (! empty($chosen)) {
            return;
        }

        $pending = Shift::where('sw_shifts.business_id', $businessId)
            ->where('sw_shifts.location_id', $locationId)
            ->where(function ($statusQuery) {
                $statusQuery
                    ->whereRaw("LOWER(TRIM(CAST(sw_shifts.status AS CHAR))) IN ('1', 'closed', 'close')");

                if (Schema::hasColumn('sw_shifts', 'closed_at')) {
                    $statusQuery->orWhere(function ($legacy) {
                        $legacy
                            ->whereRaw("LOWER(TRIM(CAST(sw_shifts.status AS CHAR))) IN ('0', 'open', 'opened')")
                            ->whereNotNull('sw_shifts.closed_at');
                    });
                }
            })
            ->when(
                Schema::hasColumn('sw_shifts', 'deleted_at'),
                fn ($q) => $q->whereNull('sw_shifts.deleted_at')
            )
            ->whereExists(function ($q) use ($operatorId) {
                $q->select(DB::raw(1))
                  ->from('sw_shift_operators')
                  ->whereColumn('sw_shift_operators.sw_shift_id', 'sw_shifts.id')
                  ->where('sw_shift_operators.pump_operator_id', $operatorId);
            })
            ->when(Schema::hasTable('sw_settlement_shifts'), function ($q) {
                $q->whereNotExists(function ($settled) {
                    $settled->select(DB::raw(1))
                        ->from('sw_settlement_shifts')
                        ->whereColumn('sw_settlement_shifts.sw_shift_id', 'sw_shifts.id');
                });
            })
            ->pluck('sw_shifts.sw_shift_no');

        abort_if(
            $pending->isNotEmpty(),
            422,
            __('sw::lang.pending_shift_must_be_settled_named', ['numbers' => $pending->implode(', ')])
        );
    }

    /**
     * 8043: not before the system opening date, and not into closed accounts.
     *
     * Checked again here even though the picker restricts it - a picker is a
     * convenience, not a control, and the date can still arrive by other means.
     */
    protected function assertDateAllowed(int $businessId, string $date): void
    {
        $settlementDate = Carbon::parse($date)->startOfDay();
        $opening = $this->openingDate($businessId);

        abort_if(
            $opening && $settlementDate->lt(Carbon::parse($opening)->startOfDay()),
            422,
            __('sw::lang.date_before_opening', ['date' => $opening])
        );

        /*
         | IS2230 ROOT CAUSE.
         |
         | accounts_closed_date means "books are closed THROUGH this date".
         | The old code treated it as the LAST date allowed and rejected every
         | later date, which is the reverse of normal accounting close-period
         | behaviour and caused the 422 shown in the issue document.
        */
        $closedThrough = $this->accountsClosedThroughDate($businessId);
        abort_if(
            $closedThrough
                && $settlementDate->lte(Carbon::parse($closedThrough)->startOfDay()),
            422,
            __('sw::lang.date_in_closed_period')
        );

        // A settlement cannot be dated into the future.
        abort_if(
            $settlementDate->gt(Carbon::today()),
            422,
            __('sw::lang.date_in_future')
        );
    }

    /** Convert the visible dd/mm/yyyy field into the ISO date stored by SW. */
    protected function normaliseSettlementDate(string $date): string
    {
        $date = trim($date);

        // The configured business format is authoritative for ambiguous values
        // such as 09/10/2026. Fallbacks exist only for older cached pages/API
        // callers that may still submit a different known format.
        $formats = array_values(array_unique(array_filter([
            $this->settlementDateFormat($this->businessId()),
            'Y-m-d',
            'm/d/Y',
            'd/m/Y',
            'm-d-Y',
            'd-m-Y',
        ])));

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat('!' . $format, $date);
            $errors = \DateTime::getLastErrors();
            $clean = $errors === false
                || ((int) ($errors['warning_count'] ?? 0) === 0 && (int) ($errors['error_count'] ?? 0) === 0);

            if ($parsed && $clean && $parsed->format($format) === $date) {
                return Carbon::instance($parsed)->toDateString();
            }
        }

        throw ValidationException::withMessages([
            'transaction_date' => [__('validation.date', ['attribute' => __('sw::lang.transaction_date')])],
        ]);
    }

    /** Business-configured PHP date format used by the rest of the ERP. */
    protected function settlementDateFormat(int $businessId): string
    {
        $format = (string) session('business.date_format', '');

        try {
            if ($businessId > 0 && Schema::hasTable('business') && Schema::hasColumn('business', 'date_format')) {
                $dbFormat = (string) DB::table('business')->where('id', $businessId)->value('date_format');
                if ($dbFormat !== '') {
                    $format = $dbFormat;
                }
            }
        } catch (\Throwable $e) {
            // Session value remains the safe fallback for this request.
        }

        $allowed = ['m/d/Y', 'd/m/Y', 'Y-m-d', 'm-d-Y', 'd-m-Y'];

        return in_array($format, $allowed, true) ? $format : 'm/d/Y';
    }

    /** Bootstrap-datepicker equivalent of the configured PHP date format. */
    protected function datepickerDateFormat(int $businessId): string
    {
        return match ($this->settlementDateFormat($businessId)) {
            'd/m/Y' => 'dd/mm/yyyy',
            'Y-m-d' => 'yyyy-mm-dd',
            'm-d-Y' => 'mm-dd-yyyy',
            'd-m-Y' => 'dd-mm-yyyy',
            default => 'mm/dd/yyyy',
        };
    }

    /** The business's own start date, where one is recorded. */
    protected function openingDate(int $businessId): ?string
    {
        if (! Schema::hasTable('business')) {
            return null;
        }

        foreach (['start_date', 'financial_year_start_month'] as $column) {
            if ($column === 'start_date' && Schema::hasColumn('business', 'start_date')) {
                $v = DB::table('business')->where('id', $businessId)->value('start_date');
                if (! empty($v)) {
                    return \Carbon\Carbon::parse($v)->toDateString();
                }
            }
        }

        return null;
    }

    /** Latest selectable settlement date: never later than today. */
    protected function maxDate(int $businessId): string
    {
        return Carbon::today()->toDateString();
    }

    /** Date through which the books have already been closed. */
    protected function accountsClosedThroughDate(int $businessId): ?string
    {
        if (! Schema::hasTable('business')
            || ! Schema::hasColumn('business', 'accounts_closed_date')) {
            return null;
        }

        $value = DB::table('business')->where('id', $businessId)->value('accounts_closed_date');

        if (empty($value)) {
            return null;
        }

        $configured = Carbon::parse($value)->startOfDay();

        /*
         | IS2232 compatibility. Some older ERP screens used this column as a
         | FUTURE accounting closing date, while newer close-period logic uses
         | it as "closed through". Treating a future date as already closed
         | blocks every current settlement. Only a date that is today or in the
         | past can represent a period that has actually been closed.
        */
        if ($configured->gt(Carbon::today())) {
            return null;
        }

        return $configured->toDateString();
    }

    /**
     * First date the datepicker may offer.
     *
     * It is the later of the system opening date and the day AFTER the
     * accounts-closed-through date.  This prevents a user from completing a
     * settlement only to be refused on Save.
     */
    protected function minimumSettlementDate(int $businessId): ?string
    {
        $candidates = [];

        $opening = $this->openingDate($businessId);
        if ($opening) {
            $candidates[] = Carbon::parse($opening)->startOfDay();
        }

        $closedThrough = $this->accountsClosedThroughDate($businessId);
        if ($closedThrough) {
            $candidates[] = Carbon::parse($closedThrough)->startOfDay()->addDay();
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn (Carbon $a, Carbon $b) => $a->timestamp <=> $b->timestamp);

        return end($candidates)->toDateString();
    }

    protected function locations(int $businessId)
    {
        return DB::table('business_locations')
            ->where('business_id', $businessId)
            ->when(Schema::hasColumn('business_locations', 'is_active'),
                fn ($q) => $q->where('is_active', 1))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Location-scoped operators relevant to SW settlement.
     *
     * IS2230 requires current OPEN-shift operators.  Settlement itself,
     * however, can only consume CLOSED pending shifts.  Include both states so
     * the dropdown remains usable through the close -> settle transition, then
     * let availableShifts() expose only CLOSED, not-yet-settled shifts.
     *
     * Older SW databases may store status as 'open'/'closed' text while the
     * current schema stores 0/1.  Comparing a text column directly with 0 is
     * unsafe in MySQL because non-numeric strings can coerce to zero, so status
     * is normalised to CHAR explicitly.
     *
     * @return array<string, array<int, array{id:int,name:string}>>
     */
    protected function settlementOperatorsByLocation(int $businessId, array $locationIds): array
    {
        $locationIds = array_values(array_unique(array_filter(array_map('intval', $locationIds))));

        if ($businessId <= 0 || empty($locationIds)
            || ! Schema::hasTable('pump_operators')
            || ! Schema::hasTable('sw_shift_operators')
            || ! Schema::hasTable('sw_shifts')) {
            return [];
        }

        $hasSettlementShiftLinks = Schema::hasTable('sw_settlement_shifts');

        $query = DB::table('pump_operators as po')
            ->join('sw_shift_operators as so', 'so.pump_operator_id', '=', 'po.id')
            ->join('sw_shifts as s', 's.id', '=', 'so.sw_shift_id')
            ->where('po.business_id', $businessId)
            ->where('po.active', 1)
            ->where('s.business_id', $businessId)
            ->whereIn('s.location_id', $locationIds)
            ->where(function ($state) use ($hasSettlementShiftLinks) {
                // Current/open assignment requested in IS2230.
                $state->where(function ($open) {
                        $open->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('0', 'open', 'opened')");
                        if (Schema::hasColumn('sw_shifts', 'closed_at')) {
                            $open->whereNull('s.closed_at');
                        }
                    })
                    // Or a shift that has just been closed and is waiting for
                    // settlement. This is the state the settlement can consume.
                    ->orWhere(function ($pendingClosed) use ($hasSettlementShiftLinks) {
                        $pendingClosed->where(function ($closedState) {
                            $closedState
                                ->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('1', 'closed', 'close')");

                            if (Schema::hasColumn('sw_shifts', 'closed_at')) {
                                $closedState->orWhere(function ($legacyClosed) {
                                    $legacyClosed
                                        ->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('0', 'open', 'opened')")
                                        ->whereNotNull('s.closed_at');
                                });
                            }
                        });

                        if ($hasSettlementShiftLinks) {
                            $pendingClosed->whereNotExists(function ($settled) {
                                $settled->select(DB::raw(1))
                                    ->from('sw_settlement_shifts')
                                    ->whereColumn('sw_settlement_shifts.sw_shift_id', 's.id');
                            });
                        }
                    });
            });

        if (Schema::hasColumn('sw_shifts', 'deleted_at')) {
            $query->whereNull('s.deleted_at');
        }

        $rows = $query
            ->distinct()
            ->orderBy('s.location_id')
            ->orderBy('po.name')
            ->get([
                's.location_id',
                'po.id as operator_id',
                'po.name as operator_name',
            ]);

        $grouped = [];
        foreach ($rows as $row) {
            $locationKey = (string) ((int) $row->location_id);
            $operatorId = (int) $row->operator_id;

            if (! isset($grouped[$locationKey])) {
                $grouped[$locationKey] = [];
            }

            $grouped[$locationKey][$operatorId] = [
                'id' => $operatorId,
                'name' => (string) $row->operator_name,
            ];
        }

        foreach ($grouped as $locationKey => $operators) {
            $grouped[$locationKey] = array_values($operators);
        }

        return $grouped;
    }

    /** Expense categories available to the currently selected business. */
    protected function expenseCategories(int $businessId)
    {
        /*
         | IS2267: Expenses New / Categories writes expnew_categories and uses
         | the selected SESSION business before auth()->user()->business_id.
         | Match that precedence here so the initial Blade options and the live
         | AJAX refresh are scoped to the same business as Categories / Add.
        */
        $session = request()->hasSession() ? request()->session() : null;
        $categoryBusinessId = 0;

        foreach ([
            $session?->get('user.business_id'),
            $session?->get('business.id'),
            $session?->get('business_id'),
            session('user.business_id'),
            session('business.id'),
            session('business_id'),
            $businessId,
        ] as $candidate) {
            if ((int) $candidate > 0) {
                $categoryBusinessId = (int) $candidate;
                break;
            }
        }

        return $this->expenseCategoryLookup->options($categoryBusinessId);
    }
}
