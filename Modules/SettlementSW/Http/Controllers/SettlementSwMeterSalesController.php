<?php

namespace Modules\SettlementSW\Http\Controllers;

use Illuminate\Http\Request;
use Modules\SettlementSW\Entities\MeterSale;

/**
 * SW_SEP_002
 * Focused Settlement SW controller wrapper.
 *
 * This class intentionally inherits the existing stable behaviour from
 * SettlementSwBaseController and allows the route layer to be separated by responsibility
 * without changing working settlement logic in this step.
 */
class SettlementSwMeterSalesController extends SettlementSwBaseController
{
    // Behaviour is inherited safely from SettlementSwBaseController.

    public function getMeterSaleForm($id)
    {
        $meter_sale = MeterSale::find($id);
        $meter_sale_id = $id;

        return view('settlementsw::swsettlement.meter_sales.form', compact('meter_sale', 'meter_sale_id'));
    }

    public function updateSettlementMeterSale(Request $request, $id)
    {
        $meterSale = MeterSale::findOrFail($id);
        $meterSale->fill($request->except(['_token', '_method']));
        $meterSale->save();

        return response()->json(['success' => 1, 'msg' => __('Meter sale updated successfully')]);
    }
}

