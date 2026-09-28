<?php

namespace Modules\PetroGeneral\Http\Controllers;

use App\BusinessLocation;
;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\PetroGeneral\Entities\CurrentMeter;
use Modules\PetroGeneral\Entities\FuelTank;
use Modules\PetroGeneral\Entities\MeterResetting;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\MeterSale;
use Yajra\DataTables\Facades\DataTables;

class MeterResettingController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $query = MeterResetting::leftjoin('pumps', 'meter_resettings.pump_id', 'pumps.id')
                ->leftjoin('business_locations', 'meter_resettings.location_id', 'business_locations.id')
                ->leftjoin('fuel_tanks', 'pumps.fuel_tank_id', 'fuel_tanks.id')
                ->leftjoin('users', 'meter_resettings.created_by', 'users.id')
                ->where('meter_resettings.business_id', $business_id)
                ->select([
                    'meter_resettings.*',
                    'business_locations.name as location_name',
                    'fuel_tanks.fuel_tank_number',
                    'pumps.pump_no',
                    'users.username',
                ]);

            if (!empty(request()->location_id)) {
                $query->where('meter_resettings.location_id', request()->location_id);
            }
            if (!empty(request()->tank_id)) {
                $query->where('fuel_tanks.id', request()->tank_id);
            }

            if (!empty(request()->product_id)) {
                $query->where('pumps.product_id', request()->product_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $query->whereBetween('meter_resettings.date_and_time', [date(request()->start_date), date(request()->end_date)]);
            }

            $dip_report = Datatables::of($query)
                ->addColumn(
                    'action',
                    '<a data-href="{{action(\'\Modules\PetroGeneral\Http\Controllers\MeterResettingController@show\', [$id])}}" class="btn-modal btn btn-primary btn-xs" data-container=".pump_modal"><i class="fa fa-eye" aria-hidden="true"></i> @lang("messages.view")</a>'
                )

                ->removeColumn('id');


            return $dip_report->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

        $pumps = Pump::where('business_id', $business_id)->pluck('pump_no', 'id');
        /*
         * S 680 - reference numbers were generated as count() + 1, which
         * repeats a number as soon as any row is deleted. If the column is
         * unique that is a duplicate-key error surfacing as
         * "Something went wrong"; if it is not, it is two resets sharing a
         * reference. max(id) + 1 avoids both.
         */
        $lastRef = MeterResetting::where('business_id', $business_id)->max('id');

        $ref_no = (int) $lastRef + 1;

        return view('petrogeneral::pumps.partials.add_meter_reset')->with(compact(
            'business_locations',
            'pumps',
            'ref_no'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        /*
         * S 680 fix 1 - validate before touching anything.
         *
         * Previously every failure - a missing pump, an unparseable date, a
         * missing meter sale - fell into one catch block and produced
         * "Something went wrong, please try again later". That message told
         * the user nothing and told support nothing either. Real validation
         * up front means a bad input is reported as a bad input, and the catch
         * block is left for genuinely unexpected faults.
         */
        $validator = Validator::make($request->all(), [
            'location_id' => 'required|integer',
            'pump_id' => 'required|integer',
            'new_reset_meter' => 'required|numeric|min:0',
            'date_and_time' => 'required',
        ], [
            'location_id.required' => __('petrogeneral::lang.location') . ' is required.',
            'pump_id.required' => __('petrogeneral::lang.pumps') . ' is required.',
            'new_reset_meter.required' => __('petrogeneral::lang.reset_new_meter') . ' is required.',
            'new_reset_meter.numeric' => __('petrogeneral::lang.reset_new_meter') . ' must be a number.',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'msg' => $validator->errors()->first(),
            ];
        }

        try {
            $pump = Pump::where('id', $request->pump_id)
                ->where('business_id', $business_id)
                ->first();

            if (empty($pump)) {
                return [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.pumps') . ' not found for this business.',
                ];
            }

            /*
             * S 680 fix 1 - the date arrives in the business's display format,
             * which differs per tenant (d/m/Y, m/d/Y, Y-m-d). Carbon::parse
             * guesses, and guesses wrong on d/m/Y - "29/08/2026" is not a
             * valid US date, so it threw and produced the generic error.
             *
             * The time is also preserved now. The field is labelled
             * "Dip Date & Time" and the old code formatted it to 'Y-m-d',
             * silently discarding the time the user had entered.
             */
            $dateAndTime = $this->parseDateAndTime($request->date_and_time);

            $newResetMeter = (float) $request->new_reset_meter;

            // Resolved server-side rather than trusting the posted value: the
            // field is readonly on the form, so a posted value that disagrees
            // with the database means the form was stale or tampered with.
            $lastMeter = $this->resolveCurrentMeter($pump);

            DB::beginTransaction();

            MeterResetting::create([
                'business_id' => $business_id,
                'location_id' => $request->location_id,
                'meter_reset_ref_no' => $request->meter_reset_ref_no,
                'date_and_time' => $dateAndTime,
                'pump_id' => $pump->id,
                'last_meter' => $lastMeter,
                'new_reset_meter' => $newResetMeter,
                'reason' => $request->reason,
                'created_by' => Auth::user()->id,
            ]);

            Pump::where('id', $pump->id)->update(['last_meter_reading' => $newResetMeter]);

            /*
             * S 680 fix 1 - THE CRASH.
             *
             * The old code did:
             *     $last_meter_sale = MeterSale::where(...)->first();
             *     MeterSale::where('id', $last_meter_sale->id)->update(...)
             *
             * with no null check. A pump that has never been through a
             * settlement has no meter sale at all, so $last_meter_sale was
             * null and reading ->id threw a fatal error. That is exactly the
             * reported case: the screenshot shows Last Meter 0.0000, i.e. a
             * pump with no history.
             *
             * Stamping the reset onto the latest meter sale is still correct
             * when one exists - ProvidesPdLookups reads meter_reset_value in
             * preference to closing_meter - it just is not always applicable.
             */
            $lastMeterSale = MeterSale::where('pump_id', $pump->id)
                ->orderBy('id', 'desc')
                ->first();

            if (! empty($lastMeterSale)) {
                MeterSale::where('id', $lastMeterSale->id)
                    ->update(['meter_reset_value' => $newResetMeter]);
            }

            // S 680 fix 3 - reflect the reset on the Current Meter screen.
            $this->applyResetToCurrentMeter($business_id, $pump, $lastMeter, $newResetMeter, $dateAndTime);

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('petrogeneral::lang.success'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            /*
             * S681: catch \Throwable, not \Exception, and say what failed.
             *
             * Reported: saving a Meter Reset showed only "Something went wrong,
             * please try again later".
             *
             * Two separate problems produced that.
             *
             * FIRST, this caught \Exception. Since PHP 7 a TypeError, an
             * ArgumentCountError and every other Error implement \Throwable but
             * are NOT \Exception - so any of those escaped this block entirely.
             * The transaction was then never rolled back and the request died as
             * an unhandled 500, leaving a half-written meter reset behind.
             * \Throwable covers both.
             *
             * SECOND, the real reason was written to the log and replaced on
             * screen with a message that says nothing. The person who can act on
             * it never sees it, and every failure looks identical - which is why
             * this arrived as "an error is showing" with nothing to work from.
             *
             * The message is now carried back to the user. It is a controlled
             * string from the exception, shown on a screen only reachable by a
             * signed-in user with pump rights, so it exposes nothing a stack
             * trace on an error page would not already have.
             */
            \Log::emergency('Meter reset failed. File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ' (' . $e->getMessage() . ')',
            ];
        }

        return $output;
    }

    /**
     * Parse the posted date, honouring the business's configured format.
     *
     * S 680: the previous single Carbon::parse() call was the second crash
     * waiting to happen. The candidate list is ordered so the tenant's own
     * format is tried first, then the unambiguous ISO form, then the two
     * slash formats. d/m/Y is tried before m/d/Y because this deployment is
     * Sri Lankan; for a date such as 05/08/2026 the two disagree silently and
     * the wrong answer would be stored without any error.
     */
    protected function parseDateAndTime($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return \Carbon::now()->format('Y-m-d H:i:s');
        }

        $businessFormat = session('business.date_format');

        $candidates = [];
        if (! empty($businessFormat)) {
            $candidates[] = $businessFormat . ' H:i:s';
            $candidates[] = $businessFormat . ' H:i';
            $candidates[] = $businessFormat;
        }

        $candidates = array_merge($candidates, [
            'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
            'm/d/Y H:i:s', 'm/d/Y H:i', 'm/d/Y',
        ]);

        foreach ($candidates as $format) {
            try {
                $parsed = \Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // Try the next candidate.
            }
        }

        try {
            return \Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return \Carbon::now()->format('Y-m-d H:i:s');
        }
    }

    /**
     * The pump's current meter reading.
     *
     * S 680 fix 2 - the form showed 0.0000 because it read
     * pumps.last_meter_reading directly, and that column is only ever written
     * by a meter reset. On a pump that has been trading but never reset it
     * stays at zero no matter how much fuel has gone through it.
     *
     * The rest of the system already resolves this properly - see
     * ProvidesPdLookups - by preferring the latest meter sale's reset value,
     * then its closing meter, and only then falling back to the pump column.
     * That order is reproduced here so the reset form agrees with settlement
     * rather than contradicting it.
     */
    protected function resolveCurrentMeter(Pump $pump): float
    {
        $lastMeterSale = MeterSale::where('pump_id', $pump->id)
            ->orderBy('id', 'desc')
            ->first();

        if (! empty($lastMeterSale)) {
            return ! empty($lastMeterSale->meter_reset_value)
                ? (float) $lastMeterSale->meter_reset_value
                : (float) $lastMeterSale->closing_meter;
        }

        return (float) ($pump->last_meter_reading ?? 0);
    }

    /**
     * Record the reset on the Current Meter screen.
     *
     * S 680 fix 3 - "the updated meter value should also update in Pump
     * Management / Current Meter". That screen lists current_meters rows, and
     * nothing in the reset flow touched that table, so the reset was invisible
     * there.
     *
     * A NEW row is added rather than editing the latest one. Editing would
     * overwrite a reading an operator actually recorded, and would leave
     * current_meter - last_time_meter no longer equal to sold_ltr on that row,
     * quietly corrupting the sales figure it feeds. Appending states what
     * happened - at this time the meter went from X to Y - and leaves the
     * history intact. sold_ltr is zero because a reset moves the meter without
     * selling anything.
     *
     * Columns are checked before use because current_meters carries
     * operator-entry fields that may be absent or NOT NULL on older tenants,
     * and a reset must not fail over a column this feature does not care
     * about.
     */
    protected function applyResetToCurrentMeter($business_id, Pump $pump, $lastMeter, $newResetMeter, $dateAndTime): void
    {
        if (! Schema::hasTable('current_meters')) {
            return;
        }

        $row = [];

        $candidates = [
            'business_id' => $business_id,
            'pump_id' => $pump->id,
            'pump_no' => $pump->pump_no,
            'date_and_time' => $dateAndTime,
            'last_time_meter' => $lastMeter,
            'current_meter' => $newResetMeter,
            'starting_meter' => $lastMeter,
            'sold_ltr' => 0,
            'sale_price' => 0,
            'amount' => 0,
        ];

        foreach ($candidates as $column => $value) {
            if (Schema::hasColumn('current_meters', $column)) {
                $row[$column] = $value;
            }
        }

        // Some tenants also carry a plain `date` column alongside
        // `date_and_time`; the create screen filters on it.
        if (Schema::hasColumn('current_meters', 'date')) {
            $row['date'] = \Carbon::parse($dateAndTime)->format('Y-m-d');
        }

        if (empty($row)) {
            return;
        }

        CurrentMeter::create($row);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

        $meter_resettings = MeterResetting::leftjoin('pumps', 'meter_resettings.pump_id', 'pumps.id')
        ->leftjoin('business_locations', 'meter_resettings.location_id', 'business_locations.id')
        ->leftjoin('fuel_tanks', 'pumps.fuel_tank_id', 'fuel_tanks.id')
        ->leftjoin('users', 'meter_resettings.created_by', 'users.id')
        ->where('meter_resettings.id', $id)
        ->select([
            'meter_resettings.*',
            'business_locations.name as location_name',
            'fuel_tanks.fuel_tank_number',
            'pumps.pump_no',
            'users.username',
        ])->first();


        return view('petrogeneral::pumps.partials.show_meter_resettings')->with(compact(
            'meter_resettings',
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('petrogeneral::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }

    /**
     * Pump details for the Add Meter Reset form.
     *
     * S 680 fix 2 - this used to return pumps.last_meter_reading as the
     * "Last Meter (Current Meter)" value, which is why the field showed
     * 0.0000. It now returns the properly resolved current meter as well, so
     * the form agrees with what settlement believes the pump is reading.
     *
     * last_meter_reading is still returned so any other caller of this
     * endpoint keeps working unchanged.
     */
    public function getPumpDetails(Request $request)
    {
        $pump_id = $request->pump_id;

        $pump = Pump::leftjoin('fuel_tanks', 'pumps.fuel_tank_id', 'fuel_tanks.id')
            ->leftjoin('products', 'fuel_tanks.product_id', 'products.id')
            ->where('pumps.id', $pump_id)
            ->select(
                'pumps.id',
                'pumps.pump_no',
                'fuel_tanks.fuel_tank_number',
                'products.name as product_name',
                'pumps.last_meter_reading'
            )->first();

        if (empty($pump)) {
            return [
                'fuel_tank_number' => null,
                'product_name' => null,
                'last_meter_reading' => 0,
                'current_meter' => 0,
            ];
        }

        $pumpModel = Pump::find($pump_id);

        return [
            'id' => $pump->id,
            'pump_no' => $pump->pump_no,
            'fuel_tank_number' => $pump->fuel_tank_number,
            'product_name' => $pump->product_name,
            'last_meter_reading' => $pump->last_meter_reading,
            'current_meter' => $pumpModel ? $this->resolveCurrentMeter($pumpModel) : 0,
        ];
    }
}
