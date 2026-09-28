<?php

namespace Modules\SettlementSW\Http\Controllers;

use Modules\SettlementSW\Entities\CustomerPayment;

/**
 * SW_SEP_002
 * Focused Settlement SW controller wrapper.
 *
 * This class intentionally inherits the existing stable behaviour from
 * SettlementSwBaseController and allows the route layer to be separated by responsibility
 * without changing working settlement logic in this step.
 */
class SettlementSwCustomerPaymentController extends SettlementSwBaseController
{
    // Behaviour is inherited safely from SettlementSwBaseController.

    public function deleteCustomerPayment($id)
    {
        CustomerPayment::where('id', $id)->delete();
        return response()->json(['success' => 1, 'msg' => __('Customer payment deleted successfully')]);
    }
}

