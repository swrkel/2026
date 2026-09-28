<?php
namespace Modules\Tailoring\Reports\Services;
use Illuminate\Http\Request;
class TailoringReportCentreService
{
    public function summary(Request $request): array
    {
        return ['reports'=>0,'scheduled'=>0,'exports_today'=>0,'failed_exports'=>0,'pending_checks'=>0,'readiness'=>0];
    }
    public function reportGroups(): array
    {
        return ['Sales','Production','Materials','Quality','Delivery','Customer','Employee','Profitability','Branch','Executive'];
    }
}
