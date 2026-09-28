<?php

namespace Modules\MPCS\Http\Controllers\Reports;

use Modules\MPCS\Http\Controllers\F16AFormController;

/**
 * Compatibility controller for routes that still reference the historical
 * Reports\F16AReportController class.
 *
 * All working F16A behaviour remains in F16AFormController.
 */
class F16AReportController extends F16AFormController
{
    // Intentionally inherits all actions and constructor dependencies.
}
