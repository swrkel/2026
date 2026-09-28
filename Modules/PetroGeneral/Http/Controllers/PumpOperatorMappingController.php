<?php
namespace Modules\PetroGeneral\Http\Controllers;

use App\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorMapping;
use Yajra\DataTables\Facades\DataTables;

class PumpOperatorMappingController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index()
    {
        if (! Schema::hasTable('pump_operator_mappings')) {
            return response()->json([
                'data'            => [],
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
            ]);
        }

        $data = PumpOperatorMapping::join('pump_operators as po', 'po.id', '=', 'pump_operator_mappings.operator_id')
            ->join('pumps as p', 'p.id', '=', 'pump_operator_mappings.pump_id')
            ->select(
                'pump_operator_mappings.operator_id',
                'po.name as operator_name',
                'pump_operator_mappings.assigned_at',
                DB::raw("GROUP_CONCAT(p.pump_name ORDER BY p.id SEPARATOR ', ') as pumps"),
                DB::raw("DATE(pump_operator_mappings.assigned_at) as assigned_date"),
                DB::raw("TIME(pump_operator_mappings.assigned_at) as assigned_time")
            )
            ->groupBy('pump_operator_mappings.operator_id', 'po.name');
        return DataTables::of($data)
            ->addColumn('action', function ($row) {
                $html = '<a data-href="' . action([\Modules\PetroGeneral\Http\Controllers\PumpOperatorMappingController::class, 'edit'], ['pump_operator_mapping' => $row->operator_id]) . '"
class="cursor-pointer view_a_campaign btn btn-xs btn-info m-2 btn-modal" data-container=".edit_pump_to_operator_modal">
                            <i class="fa fa-edit"></i>
                             ' . __("messages.edit") . '
                            </a>';
                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('issue_customer_bill.add')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id    = request()->session()->get('business.id');
        $pumps          = Pump::where('business_id', $business_id)->pluck('pump_name', 'id');
        $pump_operators = PumpOperator::where('business_id', $business_id)->where('active', 1)->pluck('name', 'id');

        return view('petrogeneral::issue_bill_customer.partials.create_pump_operator_mapping')->with(compact(
            'pumps',
            'pump_operators',
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $mappings = $request->input('mappings', []);

        if (empty($mappings)) {
            return response()->json([
                'success' => false,
                'message' => 'No pump/operator mappings provided',
            ], 422);
        }

        $allPumpIds = [];

        $date = Carbon::createFromFormat(
            'm/d/Y h:i A',
            $request->input('date')
        );

        foreach ($mappings as $mapping) {

            if (
                empty($mapping['operator_id']) ||
                empty($mapping['pump_ids']) ||
                ! is_array($mapping['pump_ids'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid mapping data',
                ], 422);
            }

            foreach ($mapping['pump_ids'] as $pumpId) {
                if (in_array($pumpId, $allPumpIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pump is duplicated between operators',
                    ]);
                }
                $allPumpIds[] = $pumpId;
            }
        }

        $existingPump = PumpOperatorMapping::join(
            'pumps',
            'pumps.id',
            '=',
            'pump_operator_mappings.pump_id'
        )
            ->whereIn('pump_operator_mappings.pump_id', $allPumpIds)
            ->pluck('pumps.pump_name')
            ->toArray();

        if (! empty($existingPump)) {
            return response()->json([
                'success' => false,
                'message' => 'The following pumps already exist: ' . implode(', ', $existingPump),
            ]);
        }

        try {
            DB::beginTransaction();

            foreach ($mappings as $mapping) {
                foreach ($mapping['pump_ids'] as $pumpId) {
                    PumpOperatorMapping::create([
                        'assigned_at' => $date->format('Y-m-d H:i:s'),
                        'operator_id' => $mapping['operator_id'],
                        'pump_id'     => $pumpId,
                        'business_id' => session('user.business_id'),
                        'created_by'  => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('petrogeneral::lang.success'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('issue_customer_bill.add')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id         = request()->session()->get('business.id');
        $pumpMaterialMapping = PumpOperatorMapping::where('operator_id', $id)->get();

        $pumps          = Pump::where('business_id', $business_id)->pluck('pump_name', 'id');
        $pump_operators = PumpOperator::where('business_id', $business_id)->where('active', 1)->pluck('name', 'id');

        $pumpIds      = $pumpMaterialMapping->pluck('pump_id')->filter()->values()->toArray();
        $firstMapping = $pumpMaterialMapping->first();
        $productIds   = Pump::whereIn('id', $pumpIds)->pluck('product_id')->filter()->values()->toArray();
        $products     = Product::whereIn('id', $productIds)->get();
        return view('petrogeneral::issue_bill_customer.partials.edit_pump_operator_mapping')
            ->with(compact('pumpMaterialMapping', 'pumps', 'pumpIds', 'pump_operators', 'firstMapping', 'products'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update(Request $request, $operator_id)
    {
        $pumpIds = $request->pump_id;
        $date    = Carbon::createFromFormat('m/d/Y h:i A', $request->date);

        DB::beginTransaction();
        try {
            foreach ($request->mappings as $map) {
                $operatorId = $map['operator_id'];
                $newPumpIds = $map['pump_ids'];

                $oldPumpIds = PumpOperatorMapping::where('operator_id', $operatorId)
                    ->pluck('pump_id')
                    ->toArray();

                $addedPumpIds = array_diff($newPumpIds, $oldPumpIds);

                $conflicts = PumpOperatorMapping::join('pumps', 'pump_operator_mappings.pump_id', '=', 'pumps.id')
                    ->whereIn('pump_operator_mappings.pump_id', $addedPumpIds)
                    ->where('pump_operator_mappings.operator_id', '!=', $operatorId)
                    ->select('pumps.pump_name')
                    ->get();

                if ($conflicts->count()) {
                    $conflictPumpNames = $conflicts->pluck('pump_name')->toArray();
                    $conflictList      = implode(', ', $conflictPumpNames);

                    return response()->json([
                        'success'   => false,
                        'message'   => 'Some pumps are already assigned to another operator: ' . $conflictList,
                        'conflicts' => $conflicts,
                    ]);
                }

                PumpOperatorMapping::where('operator_id', $operatorId)->delete();

                foreach ($newPumpIds as $pumpId) {
                    PumpOperatorMapping::create([
                        'operator_id' => $operatorId,
                        'pump_id'     => $pumpId,
                        'assigned_at' => $date->format('Y-m-d H:i:s'),
                        'created_by'  => auth()->user()->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('petrogeneral::lang.success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function getProductsByPumpId(Request $request)
    {
        $pumpIds  = $request->pump_ids ?? [];
        $products = Pump::join('products', 'products.id', '=', 'pumps.product_id')
            ->select(
                'products.id as product_id',
                'products.name as product_name'
            )
            ->whereIn('pumps.id', $pumpIds)
            ->get();
        return response()->json($products);
    }

    public function getLastMapping(Request $request, $pump_id)
    {
        $mapping = PumpOperatorMapping::with('operator')
            ->where('pump_id', $pump_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $mapping) {
            return response()->json([]);
        }

        return response()->json([
            [
                'operator_id'   => $mapping->operator_id,
                'operator_name' => $mapping->operator->name ?? '',
                'pump_ids'      => [(string) $mapping->pump_id],
            ],
        ]);
    }

}
