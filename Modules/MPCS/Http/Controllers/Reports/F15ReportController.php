<?php

namespace Modules\MPCS\Http\Controllers\Reports;

use Modules\MPCS\Http\Controllers\F15FormController;

/**
 * Compatibility controller for routes that reference the Reports namespace.
 *
 * The current MPCS module keeps the F15 implementation in F15FormController.
 * Extending it here allows existing report routes to resolve without duplicating
 * or changing the working F15 logic.
 */
class F15ReportController extends F15FormController
{
    // All actions are inherited from F15FormController.
}
