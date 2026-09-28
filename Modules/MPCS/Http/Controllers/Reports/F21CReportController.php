<?php

namespace Modules\MPCS\Http\Controllers\Reports;

use Modules\MPCS\Http\Controllers\F21CFormController;

/**
 * Compatibility controller for routes that reference the historical
 * Reports\F21CReportController class.
 *
 * The working 21C implementation remains in F21CFormController, so this
 * adapter keeps existing routes valid without duplicating report logic.
 */
class F21CReportController extends F21CFormController
{
    // All actions and dependencies are inherited from F21CFormController.
}
