<?php

namespace Modules\SettlementSW\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\SettlementSW\Entities\FuelTank;
use Modules\SettlementSW\Entities\Pump;
use Modules\SettlementSW\Services\SettlementSwTables;

/**
 * SW_SEP_002
 * Focused Settlement SW controller wrapper.
 *
 * This class intentionally inherits the existing stable behaviour from
 * SettlementSwBaseController and allows the route layer to be separated by responsibility
 * without changing working settlement logic in this step.
 */
class SettlementSwDashboardController extends SettlementSwBaseController
{
    // Behaviour is inherited safely from SettlementSwBaseController.

    public function update(Request $request, $id)
    {
        $data = $request->only(['note', 'work_shift', 'transaction_date', 'pump_operator_id', 'location_id']);
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        DB::table(SettlementSwTables::settlements())->where('id', $id)->update($data);

        return response()->json(['success' => 1, 'msg' => __('Settlement SW updated successfully')]);
    }

    public function getTankProduct($id)
    {
        $tank = FuelTank::query()->findOrFail($id);
        $product = DB::table(SettlementSwTables::products())->where('id', $tank->product_id)->select('id', 'name')->first();

        return response()->json($product ?: ['id' => $tank->product_id, 'name' => '']);
    }

    public function otherSalesList(Request $request)
    {
        $shiftIds = (array) $request->input('shift_ids', []);
        $query = DB::table(SettlementSwTables::pumpOperatorOtherSales() . ' as pos')
            ->leftJoin(SettlementSwTables::products() . ' as p', 'pos.product_id', '=', 'p.id')
            ->when(!empty($shiftIds), function ($q) use ($shiftIds) {
                $q->whereIn('pos.shift_id', $shiftIds);
            })
            ->select('pos.*', 'p.sku as product_sku', 'p.name as product_name');

        if ($request->boolean('get_total')) {
            $rows = $query->get();
            return response()->json([
                'success' => 1,
                'total' => $rows->sum('sub_total'),
                'pump_nos' => [],
            ]);
        }

        $rows = $query->get()->map(function ($row) {
            return [
                'product_sku' => $row->product_sku,
                'product_name' => $row->product_name,
                'qty_available' => $row->balance_stock ?? 0,
                'price' => $row->price ?? 0,
                'quantity' => $row->qty ?? 0,
                'discount_type' => $row->discount_type ?? '',
                'discount' => $row->discount ?? 0,
                'sub_total' => $row->sub_total ?? 0,
                'with_discount' => $row->sub_total ?? 0,
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function meterSalesList(Request $request)
    {
        $shiftIds = (array) $request->input('shift_ids', []);
        $rows = DB::table(SettlementSwTables::pumpOperatorMeterSaleDetails() . ' as d')
            ->leftJoin('pump_operator_meter_sales as s', 'd.sale_id', '=', 's.id')
            ->leftJoin(SettlementSwTables::products() . ' as p', 'd.product_id', '=', 'p.id')
            ->leftJoin('pumps as pump', 'd.pump_id', '=', 'pump.id')
            ->when(!empty($shiftIds), function ($q) use ($shiftIds) {
                $q->whereIn('s.shift_id', $shiftIds);
            })
            ->select('d.*', 's.shift_id', 'p.sku as product_sku', 'p.name as product_name', 'pump.pump_name as pump_name')
            ->get()
            ->map(function ($row) {
                $subTotal = $row->sub_total ?? (($row->price ?? 0) * ($row->qty ?? $row->quantity ?? 0));
                return [
                    'id' => $row->id,
                    'product_sku' => $row->product_sku,
                    'product_name' => $row->product_name,
                    'pump_name' => $row->pump_name,
                    'pump_id' => $row->pump_id,
                    'starting_meter' => $row->starting_meter ?? 0,
                    'closing_meter' => $row->closing_meter ?? 0,
                    'price' => $row->price ?? 0,
                    'quantity' => $row->qty ?? $row->quantity ?? 0,
                    'discount_type' => $row->discount_type ?? '',
                    'discount' => $row->discount ?? $row->discount_value ?? 0,
                    'testing_qty' => $row->testing_qty ?? 0,
                    'total_qty' => $row->total_qty ?? $row->qty ?? $row->quantity ?? 0,
                    'sub_total' => $subTotal,
                    'discount_amount' => $row->discount_amount ?? 0,
                    'later_settlements' => 0,
                    'transaction_id' => $row->transaction_id ?? null,
                    'bulk_tank' => $row->bulk_tank ?? 0,
                    'action' => '',
                ];
            });

        return response()->json(['data' => $rows]);
    }
}

