<?php

namespace Modules\SettlementSW\Http\Controllers;

use Modules\SettlementSW\Entities\OtherIncome;

/**
 * SW_SEP_002
 * Focused Settlement SW controller wrapper.
 *
 * This class intentionally inherits the existing stable behaviour from
 * SettlementSwBaseController and allows the route layer to be separated by responsibility
 * without changing working settlement logic in this step.
 */
class SettlementSwOtherIncomeController extends SettlementSwBaseController
{
    // Behaviour is inherited safely from SettlementSwBaseController.

    public function deleteOtherIncome($id)
    {
        OtherIncome::where('id', $id)->delete();
        return response()->json(['success' => 1, 'msg' => __('Other income deleted successfully')]);
    }
}

