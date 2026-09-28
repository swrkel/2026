<?php

namespace Modules\PetroGeneral\Http\Controllers\PumperDayEntry;

use App\Business;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroGeneral\Http\Controllers\PumperDayEntryController;
use Yajra\DataTables\Facades\DataTables;

/**
 * PUMPER-MGMT-LIVE-20260821
 * Dedicated Daily Pump Status DataTable endpoint.
 *
 * This endpoint deliberately avoids the previous multi-table join. Assignments,
 * day entries, pumps, operators and shifts are loaded separately and merged in
 * PHP so tenant schema differences and duplicate day-entry rows cannot break the
 * DataTables request.
 */
class PumperDayEntryDailyCollectionController extends PumperDayEntryController
{
    public function getDailyCollection()
    {
        $request = request();
        $draw = (int) $request->input('draw', 0);

        try {
            $user = Auth::user();
            $businessId = (int) ($request->session()->get('user.business_id') ?: ($user->business_id ?? 0));
            $onlyPumper = ! empty($request->input('only_pumper'));
            $pumpOperatorId = (int) ($user->pump_operator_id ?? 0);
            $date = Carbon::now()->toDateString();

            if (! $request->ajax() || $businessId <= 0) {
                return $this->emptyDataTableResponse($draw);
            }

            if ($onlyPumper && $pumpOperatorId <= 0) {
                return $this->emptyDataTableResponse($draw);
            }

            foreach (['pump_operator_assignments', 'pumps', 'pump_operators'] as $table) {
                if (! Schema::hasTable($table)) {
                    Log::warning('PetroGeneral Pumper Management daily collection missing table', [
                        'table' => $table,
                        'business_id' => $businessId,
                    ]);
                    return $this->emptyDataTableResponse($draw);
                }
            }

            $assignmentQuery = DB::table('pump_operator_assignments')
                ->where('business_id', $businessId)
                ->where(function ($query) use ($date) {
                    $query->whereDate('date_and_time', $date)
                        ->orWhere(function ($fallback) use ($date) {
                            $fallback->whereNull('date_and_time')
                                ->whereDate('created_at', $date);
                        });
                });

            if ($onlyPumper) {
                $assignmentQuery->where('pump_operator_id', $pumpOperatorId);
            }

            $assignments = $assignmentQuery
                ->orderBy('date_and_time')
                ->orderBy('id')
                ->get();

            if ($assignments->isEmpty()) {
                return $this->emptyDataTableResponse($draw);
            }

            $pumpIds = $assignments->pluck('pump_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
            $operatorIds = $assignments->pluck('pump_operator_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
            $shiftIds = $assignments->pluck('shift_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

            $pumps = DB::table('pumps')
                ->where('business_id', $businessId)
                ->whereIn('id', $pumpIds ?: [0])
                ->get()
                ->keyBy('id');

            $operators = DB::table('pump_operators')
                ->where('business_id', $businessId)
                ->whereIn('id', $operatorIds ?: [0])
                ->get()
                ->keyBy('id');

            $dayEntries = collect();
            if (Schema::hasTable('pumper_day_entries')) {
                $dayEntryQuery = DB::table('pumper_day_entries')
                    ->where('business_id', $businessId)
                    ->whereDate('date', $date);

                if ($onlyPumper) {
                    $dayEntryQuery->where('pump_operator_id', $pumpOperatorId);
                }

                $dayEntries = $dayEntryQuery->orderByDesc('id')->get();
            }

            // The newest day-entry wins if legacy duplicate rows exist.
            $entryByAssignment = collect();
            $entryByOperatorPump = collect();
            foreach ($dayEntries as $entry) {
                $assignmentId = (int) ($entry->pumper_assignment_id ?? 0);
                if ($assignmentId > 0 && ! $entryByAssignment->has($assignmentId)) {
                    $entryByAssignment->put($assignmentId, $entry);
                }

                $key = ((int) ($entry->pump_operator_id ?? 0)).':'.((int) ($entry->pump_id ?? 0));
                if (! $entryByOperatorPump->has($key)) {
                    $entryByOperatorPump->put($key, $entry);
                }
            }

            $shifts = collect();
            if (Schema::hasTable('petro_shifts') && ! empty($shiftIds)) {
                $shifts = DB::table('petro_shifts')
                    ->where('business_id', $businessId)
                    ->whereIn('id', $shiftIds)
                    ->get()
                    ->keyBy('id');
            }

            $locationIds = $operators->pluck('location_id')
                ->merge($pumps->pluck('location_id'))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $locations = collect();
            if (Schema::hasTable('business_locations') && ! empty($locationIds)) {
                $locations = DB::table('business_locations')
                    ->where('business_id', $businessId)
                    ->whereIn('id', $locationIds)
                    ->pluck('name', 'id');
            }

            $productIds = $pumps->pluck('product_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $prices = collect();
            if (Schema::hasTable('variations') && ! empty($productIds)) {
                $variations = DB::table('variations')
                    ->whereIn('product_id', $productIds)
                    ->orderBy('id')
                    ->get(['product_id', 'sell_price_inc_tax']);

                foreach ($variations as $variation) {
                    $productId = (int) $variation->product_id;
                    if (! $prices->has($productId)) {
                        $prices->put($productId, (float) ($variation->sell_price_inc_tax ?? 0));
                    }
                }
            }

            $businessDetails = null;
            try {
                $businessDetails = $this->businessUtil->getDetails($businessId);
            } catch (\Throwable $ignored) {
                $businessDetails = Business::find($businessId);
            }

            $rows = collect();
            $totalSoldLtr = 0.0;
            $totalTestingLtr = 0.0;
            $totalSoldAmount = 0.0;

            foreach ($assignments as $assignment) {
                $assignmentId = (int) ($assignment->id ?? 0);
                $operatorId = (int) ($assignment->pump_operator_id ?? 0);
                $pumpId = (int) ($assignment->pump_id ?? 0);
                $fallbackKey = $operatorId.':'.$pumpId;

                $entry = $entryByAssignment->get($assignmentId) ?: $entryByOperatorPump->get($fallbackKey);
                $pump = $pumps->get($pumpId);
                $operator = $operators->get($operatorId);
                $shift = $shifts->get((int) ($assignment->shift_id ?? 0));

                $startingMeter = (float) (($entry->starting_meter ?? null) ?? ($assignment->starting_meter ?? 0));
                $closingMeter = (float) (($entry->closing_meter ?? null) ?? ($assignment->closing_meter ?? 0));
                $soldLtr = (float) ($entry->sold_ltr ?? 0);
                $testingLtr = (float) ($entry->testing_ltr ?? 0);
                $amount = (float) ($entry->amount ?? 0);
                $productId = (int) ($pump->product_id ?? 0);

                if ($amount == 0.0 && $soldLtr != 0.0) {
                    $amount = $soldLtr * (float) $prices->get($productId, 0);
                }

                $dateAndTime = $assignment->date_and_time ?? $assignment->created_at ?? null;
                if ($entry && ! empty($entry->date)) {
                    $dateAndTime = trim((string) $entry->date.' '.(string) ($entry->time ?? '00:00:00'));
                }

                $locationId = (int) (($operator->location_id ?? null) ?: ($pump->location_id ?? 0));

                $row = (object) [
                    'assignment_id' => $assignmentId,
                    'day_entry_id' => (int) ($entry->id ?? 0),
                    'assignment_status' => (string) ($assignment->status ?? ''),
                    'is_confirmed' => (int) ($assignment->is_confirmed ?? 0),
                    'shift_id' => (int) ($assignment->shift_id ?? 0),
                    'shift_number' => (string) ($assignment->shift_number ?? ''),
                    'date_and_time' => $dateAndTime,
                    'pump_no' => (string) ($pump->pump_no ?? ''),
                    'name' => (string) ($operator->name ?? ''),
                    'location_name' => (string) $locations->get($locationId, ''),
                    'settlement_no' => (string) ($entry->settlement_no ?? ''),
                    'shift_status' => (int) ($shift->status ?? 0),
                    'starting_meter' => $startingMeter,
                    'closing_meter' => $closingMeter,
                    'sold_ltr_value' => $soldLtr,
                    'testing_ltr_value' => $testingLtr,
                    'sold_amount_value' => $amount,
                ];

                $rows->push($row);
                $totalSoldLtr += $soldLtr;
                $totalTestingLtr += $testingLtr;
                $totalSoldAmount += $amount;
            }

            return DataTables::of($rows)
                ->addColumn('action', fn ($row) => $this->buildActionColumn($row))
                ->editColumn('date_and_time', function ($row) {
                    if (empty($row->date_and_time)) {
                        return '';
                    }
                    try {
                        return Carbon::parse($row->date_and_time)->format('Y-m-d H:i');
                    } catch (\Throwable $ignored) {
                        return (string) $row->date_and_time;
                    }
                })
                ->addColumn('sold_ltr', function ($row) {
                    $value = (float) $row->sold_ltr_value;
                    return '<span class="display_currency footer_sold_fuel_qty" data-orig-value="'.$value.'" data-currency_symbol="false">'.$this->productUtil->num_f($value).'</span>';
                })
                ->addColumn('testing_ltr', function ($row) {
                    $value = (float) $row->testing_ltr_value;
                    return '<span class="display_currency footer_testing_qty" data-orig-value="'.$value.'" data-currency_symbol="false">'.$this->productUtil->num_f($value).'</span>';
                })
                ->addColumn('sold_amount', function ($row) use ($businessDetails) {
                    $value = (float) $row->sold_amount_value;
                    $formatted = $businessDetails
                        ? $this->commonUtil->num_f($value, false, $businessDetails, false)
                        : number_format($value, 2, '.', ',');
                    return '<span class="display_currency footer_sold_fuel_amount sold_amount" data-orig-value="'.$value.'" data-currency_symbol="false">'.$formatted.'</span>';
                })
                ->addColumn('shift_closed', fn ($row) => ((int) $row->shift_status === 2 ? 'Yes' : 'No'))
                ->rawColumns(['action', 'sold_ltr', 'testing_ltr', 'sold_amount'])
                ->with('total_sold_ltr', $this->roundQuantity($totalSoldLtr))
                ->with('total_testing_ltr', $this->roundQuantity($totalTestingLtr))
                ->with('total_sold_amount', $this->roundQuantity($totalSoldAmount))
                ->make(true);
        } catch (\Throwable $e) {
            Log::error('PetroGeneral Pumper Management daily collection DataTable failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'business_id' => $request->session()->get('user.business_id'),
                'user_id' => Auth::id(),
            ]);

            return $this->emptyDataTableResponse($draw);
        }
    }

    private function buildActionColumn($row): string
    {
        $user = Auth::user();
        if (! $user) {
            return '';
        }

        $html = '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
            .__('messages.actions').'<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu dropdown-menu-left" role="menu">';

        $canEdit = $user->can('daily_pump_status.edit');
        $assignmentId = (int) ($row->assignment_id ?? 0);
        $dayEntryId = (int) ($row->day_entry_id ?? 0);
        $shiftClosed = (int) ($row->shift_status ?? 0) === 2;
        $assignmentClosed = (string) ($row->assignment_status ?? '') === 'close';
        $receivedByPumper = ! empty($row->is_confirmed);

        if ($canEdit && $assignmentId > 0) {
            if (! $receivedByPumper && ! $shiftClosed && ! $assignmentClosed) {
                $href = action('\\Modules\\PetroGeneral\\Http\\Controllers\\PumpOperatorAssignmentController@edit', $assignmentId);
                $html .= '<li><a class="btn-modal" data-container=".pump_operator_modal" data-href="'.e($href).'"><i class="fa fa-pencil-square-o"></i> '.__('messages.edit').'</a></li>';
            } else {
                $html .= '<li class="disabled"><a href="#" class="text-muted" style="pointer-events:none;cursor:not-allowed;"><i class="fa fa-ban"></i> '.__('messages.edit').'</a></li>';
            }
        }

        if ($canEdit && $dayEntryId > 0 && empty($row->settlement_no) && empty($user->pump_operator_id)) {
            $href = action('\\Modules\\PetroGeneral\\Http\\Controllers\\PumperDayEntryController@edit', [$dayEntryId]);
            $html .= '<li><a data-href="'.e($href).'" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-pencil-square-o"></i> '.__('messages.edit').'</a></li>';
        }

        if ($user->can('daily_pump_status.delete') && $assignmentId > 0) {
            $href = action('\\Modules\\PetroGeneral\\Http\\Controllers\\PumpOperatorAssignmentController@destroy', $assignmentId);
            $html .= '<li><a href="'.e($href).'" class="delete_daily_collection"><i class="fa fa-trash"></i> '.__('messages.delete').'</a></li>';
        }

        if ($user->can('daily_pump_status.delete') && $dayEntryId > 0) {
            $href = action('\\Modules\\PetroGeneral\\Http\\Controllers\\PumperDayEntryController@destroy', $dayEntryId);
            $html .= '<li><a href="'.e($href).'" class="delete_daily_collection"><i class="fa fa-trash"></i> '.__('messages.delete').'</a></li>';
        }

        return $html.'</ul></div>';
    }

    private function emptyDataTableResponse(int $draw)
    {
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'total_sold_ltr' => '0.000',
            'total_testing_ltr' => '0.000',
            'total_sold_amount' => '0.000',
        ], 200);
    }

    private function roundQuantity($value): string
    {
        return number_format(round((float) $value, 3), 3, '.', '');
    }
}
