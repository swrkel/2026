<?php

namespace Modules\PetroPD\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PetroPD\Entities\FuelTank;

class PDFuelTankController extends Controller
{
    /**
     * PetroPD standalone tank-product endpoint used by the PD Settlement page.
     * The legacy Petro endpoint was empty in many builds, so this method returns
     * a safe JSON payload without depending on Modules\\Petro controllers.
     */
    public function getTankProduct($tank_id = null, Request $request)
    {
        $business_id = $request->session()->get('user.business_id') ?? auth()->user()->business_id ?? null;
        $tank_id = $tank_id ?: $request->get('tank_id');

        if (empty($business_id) || empty($tank_id)) {
            return response()->json(['success' => false, 'message' => 'Tank not selected.'], 422);
        }

        $tank = FuelTank::leftJoin('products', 'fuel_tanks.product_id', '=', 'products.id')
            ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->where('fuel_tanks.business_id', $business_id)
            ->where('fuel_tanks.id', $tank_id)
            ->select(
                'fuel_tanks.id as tank_id',
                'fuel_tanks.fuel_tank_number',
                'fuel_tanks.product_id',
                'products.name as product_name',
                'variations.id as variation_id',
                'variations.default_sell_price',
                'variations.sell_price_inc_tax'
            )
            ->first();

        if (empty($tank)) {
            return response()->json(['success' => false, 'message' => 'Tank not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'tank_id' => $tank->tank_id,
            'fuel_tank_number' => $tank->fuel_tank_number,
            'product_id' => $tank->product_id,
            'product_name' => $tank->product_name,
            'variation_id' => $tank->variation_id,
            'default_sell_price' => $tank->default_sell_price,
            'sell_price_inc_tax' => $tank->sell_price_inc_tax,
        ]);
    }
}
