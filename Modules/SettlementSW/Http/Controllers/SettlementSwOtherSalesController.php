<?php

namespace Modules\SettlementSW\Http\Controllers;

/**
 * SW_SEP_002
 * Focused Settlement SW controller wrapper.
 *
 * This class intentionally inherits the existing stable behaviour from
 * SettlementSwBaseController and allows the route layer to be separated by responsibility
 * without changing working settlement logic in this step.
 */
class SettlementSwOtherSalesController extends SettlementSwBaseController
{
    // Behaviour is inherited safely from SettlementSwBaseController.
}
