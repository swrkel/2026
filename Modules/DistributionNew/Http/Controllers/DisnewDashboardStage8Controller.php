<?php
namespace Modules\DistributionNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Modules\DistributionNew\Services\DisnewDashboardService;
class DisnewDashboardStage8Controller extends Controller { public function index(DisnewDashboardService $service){ $businessId=session('business.id') ?? auth()->user()->business_id ?? 0; $locationId=session('business_location_id'); $summary=$service->summary((int)$businessId, $locationId ? (int)$locationId : null); return view('distributionnew::dashboard.stage8', compact('summary')); } }
