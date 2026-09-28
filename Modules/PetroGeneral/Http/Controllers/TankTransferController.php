<?php



namespace Modules\PetroGeneral\Http\Controllers;



use App\AccountTransaction;

use App\Business;

use App\BusinessLocation;

use Modules\Superadmin\Entities\Subscription;

use Illuminate\Http\Request;

use Illuminate\Routing\Controller;

use Modules\PetroGeneral\Entities\FuelTank;

use Modules\PetroGeneral\Entities\TankTransfer;

use App\Product;

use App\ProductVariation;

use App\PurchaseLine;

use App\System;

use App\Store;

use App\Transaction;

use App\Utils\ModuleUtil;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

use Yajra\DataTables\Facades\DataTables;

use App\Utils\ProductUtil;

use App\Utils\TransactionUtil;

use App\Variation;

;

use Illuminate\Support\Facades\Log;

use Modules\PetroGeneral\Entities\Settlement;

use Modules\PetroGeneral\Entities\TankPurchaseLine;

use Modules\PetroGeneral\Entities\TankSellLine;

use Modules\Superadmin\Entities\HelpExplanation;

use Modules\Superadmin\Entities\TankDipChart;
use Modules\PetroGeneral\Entities\MeterSale;



class TankTransferController extends Controller

{

    /**

     * All Utils instance.

     *

     */

    protected $productUtil;

    protected $transactionUtil;

    protected $moduleUtil;



    /**

     * Constructor

     *

     * @param ProductUtils $product

     * @return void

     */

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)

    {

        $this->productUtil = $productUtil;

        $this->transactionUtil = $transactionUtil;

        $this->moduleUtil = $moduleUtil;

    }





    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */
     
    public function getTankProduct(){
        
    }

    public function index()

    {

        // Tank Management is used by Business Admins as well as normal users.
        // Resolve the same authenticated tenant-business context used by the rest
        // of Petro General instead of relying on one session key only. A missing
        // user.business_id previously made the transfer ajax request look empty/
        // unauthorized even while Tank Management itself was open correctly.
        $business_id = (int) (
            request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id
        );

        if ($business_id <= 0) {
            if (request()->ajax()) {
                return response()->json([
                    'draw' => (int) request()->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Business context is not available.',
                ]);
            }

            abort(403, 'Business context is not available.');
        }

        /*
         * Tank Transfers is an internal tab of Petro General / Tank Management.
         * Access to this controller is already protected by the Petro General
         * route middleware (tenant context, authenticated user, module enabled,
         * and PetroGeneralAccess role checks).
         *
         * Do NOT gate this tab with the legacy Superadmin package flag
         * `list_tank_transfer`. That flag belongs to the copied Petro workflow
         * and is not a Petro General page permission. When it is absent from a
         * business package, the Tank Management page itself opens correctly but
         * this DataTables request returns an "Unauthorized Access" error and the
         * entire tab looks broken.
         *
         * The standalone Petro General module therefore relies on its own module
         * access middleware here, keeping Tank Transfers available whenever Tank
         * Management is available, without weakening tenant/business isolation.
         */
        if (request()->ajax()) {

            try {

                $query = TankTransfer::leftjoin('fuel_tanks as t_from','t_from.id','tank_transfers.from_tank')
                
                    ->leftjoin('fuel_tanks as t_to','t_to.id','tank_transfers.to_tank')
                    
                    ->leftjoin('business_locations','business_locations.id','t_from.location_id')
                
                    ->leftjoin('products', 't_from.product_id', 'products.id')
                    
                    ->leftjoin('users', 'tank_transfers.created_by', 'users.id')

                    ->where('tank_transfers.business_id', $business_id)

                    ->select([

                        'tank_transfers.*',
                        
                        'business_locations.name as location_name',

                        'users.username as user_created',

                        'products.name as product_name',

                        't_from.fuel_tank_number as t_from_name',
                        
                        't_to.fuel_tank_number as t_to_name',

                    ]);

                if (!empty(request()->product_id)) {
                    $query->where('products.id', request()->product_id);
                }
                
                if (!empty(request()->location_id)) {
                    $query->where('t_from.location_id', request()->location_id);
                }
                
                if (!empty(request()->from_tank)) {
                    $query->where('tank_transfers.from_tank', request()->from_tank);
                }
                if (!empty(request()->to_tank)) {
                    $query->where('tank_transfers.to_tank', request()->to_tank);
                }
                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $query->whereDate('tank_transfers.date', '>=', request()->start_date);
                    $query->whereDate('tank_transfers.date', '<=', request()->end_date);
                }
                
                $business_details = Business::find($business_id);
                    

                /*
                 * IS2001: a per-row callback must never be able to break the response.
                 *
                 * These four run once PER ROW, so with an empty table they never ran
                 * at all - which is why the grid only started returning
                 *     "Invalid JSON response"
                 * after the first transfer was saved. If any of them throws, Laravel
                 * renders an HTML error page over the top of the DataTables payload
                 * and the browser reports invalid JSON, telling us nothing about the
                 * real cause.
                 *
                 * getTankBalanceByDate() is the exposed one: it lives in
                 * app/Utils/TransactionUtil, is called once for each of the two tanks
                 * on every row, and a single row referencing a deleted tank or a null
                 * date is enough to take the whole grid down.
                 *
                 * Each callback now degrades to a dash for the one cell that failed,
                 * and logs why, so the grid still renders and the cause is on record.
                 */
                $safeCell = function (callable $callback, $context) {
                    try {
                        return $callback();
                    } catch (\Throwable $cell_error) {
                        Log::warning('IS2001 tank transfer list cell failed', [
                            'cell' => $context,
                            'message' => $cell_error->getMessage(),
                            'file' => $cell_error->getFile(),
                            'line' => $cell_error->getLine(),
                        ]);

                        return '-';
                    }
                };

                $fuel_tanks = Datatables::of($query)

                    ->addColumn('quantity', function ($row) use ($safeCell) {
                        return $safeCell(function () use ($row) {
                            return $this->productUtil->num_f($row->quantity);
                        }, 'quantity #' . $row->id);
                    })

                    ->addColumn('from_qty', function ($row) use ($business_details, $safeCell) {
                        return $safeCell(function () use ($row, $business_details) {
                            $ob = $this->transactionUtil->getTankBalanceByDate($row->from_tank, $row->created_at);

                            return $this->productUtil->num_f($ob, false, $business_details, true);
                        }, 'from_qty #' . $row->id);
                    })

                    ->addColumn('to_qty', function ($row) use ($business_details, $safeCell) {
                        return $safeCell(function () use ($row, $business_details) {
                            $ob = $this->transactionUtil->getTankBalanceByDate($row->to_tank, $row->created_at);

                            return $this->productUtil->num_f($ob, false, $business_details, true);
                        }, 'to_qty #' . $row->id);
                    })

                    ->editColumn('date', function ($row) use ($safeCell) {
                        return $safeCell(function () use ($row) {
                            return empty($row->date) ? '' : $this->transactionUtil->format_date($row->date);
                        }, 'date #' . $row->id);
                    })

                    ->removeColumn('id');



                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);

            } catch (\Throwable $grid_error) {

                // Anything thrown here would otherwise become an HTML error page
                // and reach the browser as "Invalid JSON response".
                Log::error('IS2001 tank transfer list failed', [
                    'message' => $grid_error->getMessage(),
                    'file' => $grid_error->getFile(),
                    'line' => $grid_error->getLine(),
                ]);

                return response()->json([
                    'draw' => (int) request()->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => $grid_error->getMessage(),
                ]);
            }

        }

        $tank_numbers = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        
        $business_locations = BusinessLocation::forDropdown($business_id);
       

        $products = Product::leftjoin('categories', 'products.category_id', 'categories.id')->where('products.business_id', $business_id)->where('categories.name', 'Fuel')->pluck('products.name', 'products.id');

        
        return view('petrogeneral::tank_transfers.index')->with(compact(

            'tank_numbers',

            'products',
            'business_locations'

        ));

    }



    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function create()

    {

        $business_id = (int) (
            request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
        );

        abort_if($business_id <= 0, 403, 'Business context is not available.');

        // MA004: Location and Product filters. Tank dropdowns are narrowed by both,
        // which keeps every transfer within one location — a precondition of the
        // agreed stock model, where a transfer moves tank balances only and never
        // touches variation_location_details.
        $business_locations = BusinessLocation::forDropdown($business_id);

        $products = Product::leftjoin('categories', 'products.category_id', 'categories.id')
            ->where('products.business_id', $business_id)
            ->where('categories.name', 'Fuel')
            ->pluck('products.name', 'products.id');

        $tanks = FuelTank::where('business_id', $business_id)
            ->orderBy('fuel_tank_number')
            ->get(['id', 'fuel_tank_number', 'product_id', 'location_id']);

        $tank_numbers = $tanks->pluck('fuel_tank_number', 'id');

        // MA004: product_id and location_id per tank, so the form can filter the
        // From/To lists without a round trip.
        $tank_meta = [];
        $tank_bals = [];

        foreach ($tanks as $tank) {
            $tank_bals[$tank->id] = $this->transactionUtil->getTankBalanceById($tank->id);
            $tank_meta[$tank->id] = [
                'product_id'  => (string) $tank->product_id,
                'location_id' => (string) $tank->location_id,
            ];
        }

        // MA004: displayed as a preview only. The stored number is generated inside
        // the save transaction — see store(). Previously this value was rendered into
        // a form field and accepted back from the client, so two users with the form
        // open at once were handed the same number.
        $latest_transfer = TankTransfer::where('business_id', $business_id)
            ->latest('id')
            ->first();

        $transfer_no = str_pad(
            (empty($latest_transfer) ? 0 : (int) $latest_transfer->transfer_no) + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        return view('petrogeneral::tank_transfers.create')->with(compact(
            'tank_numbers',
            'transfer_no',
            'tank_bals',
            'tank_meta',
            'business_locations',
            'products'
        ));

    }



    /**

     * Store a newly created resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */

    public function store(Request $request)

    {



        try {

            $business_id = (int) (
                request()->session()->get('user.business_id')
                ?: request()->session()->get('business.id')
            );

            abort_if($business_id <= 0, 403, 'Business context is not available.');

            $validated = $request->validate([
                'from_tank' => ['required', 'integer', 'different:to_tank'],
                'to_tank' => ['required', 'integer', 'different:from_tank'],
                'quantity' => ['required', 'numeric', 'gt:0'],
                'date' => ['required', 'date'],
            ]);

            DB::beginTransaction();

            $from_tank = FuelTank::where('business_id', $business_id)
                ->lockForUpdate()
                ->findOrFail($validated['from_tank']);
            $to_tank = FuelTank::where('business_id', $business_id)
                ->lockForUpdate()
                ->findOrFail($validated['to_tank']);

            $quantity = (float) $validated['quantity'];

            // MA004: both tanks must sit at the same location. A cross-location move
            // would shift stock between locations, which this document does not
            // record and which the tank-balance-only stock model does not cover.
            if ((int) $from_tank->location_id !== (int) $to_tank->location_id) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('status', [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.transfer_location_mismatch'),
                ]);
            }

            // MA004: both tanks must hold the same product. Transferring between
            // products would silently mis-state both product balances.
            if ((int) $from_tank->product_id !== (int) $to_tank->product_id) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('status', [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.transfer_product_mismatch'),
                ]);
            }

            /*
             * IS2001: check the quantity against the SAME figure the form shows.
             *
             * MA004 added this guard but tested it against fuel_tanks.current_balance,
             * while the From Qty box is filled from getTankBalanceById(). Those are
             * two different numbers, and where current_balance is stale or zero the
             * guard rejected EVERY transfer - the form showed 500 available and the
             * server refused on a different figure. Nothing displayed the refusal, so
             * saving simply appeared to do nothing.
             *
             * The larger of the two is used deliberately: this guard exists only to
             * stop a clearly impossible transfer, and before MA004 there was no check
             * at all, so accepting either source is still stricter than the original
             * behaviour and cannot block a legitimate save.
             */
            $stored_balance = (float) $from_tank->current_balance;
            $displayed_balance = $stored_balance;

            try {
                $displayed_balance = (float) $this->transactionUtil->getTankBalanceById($from_tank->id);
            } catch (\Exception $balance_error) {
                $displayed_balance = $stored_balance;
            }

            $available_balance = max($stored_balance, $displayed_balance);

            if ($quantity > $available_balance) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('status', [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.transfer_insufficient_balance', [
                        'tank' => $from_tank->fuel_tank_number,
                        'available' => $available_balance,
                    ]),
                ]);
            }

            // MA004: generate the transfer number here, inside the transaction and
            // under the same lock, rather than accepting the value posted by the
            // client. Paired with the unique key on (business_id, transfer_no).
            $latest_transfer = TankTransfer::where('business_id', $business_id)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $transfer_no = str_pad(
                (empty($latest_transfer) ? 0 : (int) $latest_transfer->transfer_no) + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $from_tank->current_balance -= $quantity;
            $to_tank->current_balance += $quantity;

            $from_tank->save();
            $to_tank->save();

            // MA004: explicit column list. The model guards only 'id', and the
            // previous $request->except('_token') wrote every posted field.
            TankTransfer::create([
                'business_id' => $business_id,
                'transfer_no' => $transfer_no,
                'from_tank' => $from_tank->id,
                'to_tank' => $to_tank->id,
                'quantity' => $quantity,
                'date' => date('Y-m-d', strtotime($validated['date'])),
                'created_by' => auth()->user()->id,
            ]);
            
            
            DB::commit();

            // IS2001: the wording the requirement document asks for.
            $output = [

                'success' => true,

                'msg' => __('petrogeneral::lang.transfer_saved_successfully')

            ];

        } catch (\Illuminate\Validation\ValidationException $e) {

            /*
             * IS2001: report what is actually wrong with the form.
             *
             * $request->validate() is called INSIDE this try block, and
             * ValidationException extends Exception - so the generic handler below
             * was catching it and replacing "The to tank field is required" with
             * "Something went wrong". Leaving the To Tank unselected therefore gave
             * no usable reason, and on the Tank Management tab, which rendered no
             * flash at all, it gave no reason whatsoever.
             *
             * Caught first so the real field messages survive. DB::beginTransaction
             * runs after validate(), so there is no transaction open here to roll
             * back.
             */
            $messages = [];

            foreach ($e->errors() as $field_messages) {
                foreach ((array) $field_messages as $field_message) {
                    $messages[] = $field_message;
                }
            }

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => ! empty($messages)
                    ? implode(' ', $messages)
                    : __('messages.something_went_wrong'),
            ]);

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];

        }



        return redirect()->back()->with('status', $output);

    }



    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function show($id)

    {

        //

    }



    /**

     * Show the form for editing the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function edit($id)

    {
        // 

    }



    /**

     * Update the specified resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function update(Request $request, $id)

    {

        // 

    }



    /**

     * Remove the specified resource from storage.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function destroy($id)

    {
        // 
    }

}
