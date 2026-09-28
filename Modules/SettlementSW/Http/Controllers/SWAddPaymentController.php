<?php

namespace Modules\SettlementSW\Http\Controllers;

/**
 * Backward-compatible Add Payment controller alias.
 *
 * All behaviour is maintained in SettlementSwAddPaymentBaseController and its
 * focused child controllers. This wrapper prevents a second 100 KB copy of the
 * same payment logic from diverging.
 */
class SWAddPaymentController extends SettlementSwAddPaymentBaseController
{
}
