<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Modules\PetroGeneral\Http\Controllers\PumpOperatorPaymentController;

/**
 * PG014 safety wrapper.
 *
 * This class allows payment routes to be split into small controller files while
 * keeping the proven payment logic in PumpOperatorPaymentController during UAT.
 * Future PG packages can move one method at a time from the parent into the
 * relevant child controller after each section is tested.
 */
abstract class BasePaymentController extends PumpOperatorPaymentController
{
}
