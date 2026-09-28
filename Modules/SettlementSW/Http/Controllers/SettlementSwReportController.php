<?php

namespace Modules\SettlementSW\Http\Controllers;

/**
 * SW_SEP_007
 * Focused report controller for Settlement SW.
 *
 * Report routes are kept inside the SettlementSW module so report links do not
 * need external/main settlement route targets. The existing stable dashboard
 * behaviour is reused until each report gets its own small report service.
 */
class SettlementSwReportController extends SettlementSwDashboardController
{
    public function settlements()
    {
        return $this->index();
    }

    public function print($id)
    {
        return parent::print($id);
    }
}
