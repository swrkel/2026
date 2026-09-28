<?php

namespace Modules\DistributionNew\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewVehicleDocument;
use Modules\DistributionNew\Models\DisnewVehicleFuelEntry;
use Modules\DistributionNew\Models\DisnewVehicleMaintenance;
use Modules\DistributionNew\Services\Logistics\DisnewSmartLogisticsService;
use Modules\DistributionNew\Services\Logistics\DisnewVehicleDocumentExpiryService;

class LogisticsReportController extends Controller
{
    public function fuel(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewVehicleFuelEntry::forBusiness($businessId)->latest('fuel_date')->paginate(50);
        return view('distributionnew::reports.logistics.fuel', compact('records'));
    }

    public function maintenance(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewVehicleMaintenance::forBusiness($businessId)->latest('maintenance_date')->paginate(50);
        return view('distributionnew::reports.logistics.maintenance', compact('records'));
    }

    public function documentExpiry(Request $request, DisnewVehicleDocumentExpiryService $service)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $documents = $service->expiringDocuments($businessId, (int) $request->get('days', 30));
        $drivers = $service->expiringDriverLicenses($businessId, (int) $request->get('days', 30));
        return view('distributionnew::reports.logistics.document_expiry', compact('documents', 'drivers'));
    }

    public function costSummary(Request $request, DisnewSmartLogisticsService $service)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $summary = $service->vehicleCostSummary($businessId, $request->only(['date_from','date_to']));
        return view('distributionnew::reports.logistics.cost_summary', compact('summary'));
    }
}
