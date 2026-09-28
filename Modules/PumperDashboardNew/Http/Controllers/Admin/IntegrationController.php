<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Admin;

use Modules\PumperDashboardNew\Entities\PoneIntegrationLink;
use Modules\PumperDashboardNew\Entities\PoneIntegrationOutbox;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Services\Integration\PoneIntegrationProcessor;

class IntegrationController extends Controller
{
    public function __construct(private PoneIntegrationProcessor $processor) {}
    public function index()
    {
        $businessId = $this->businessId();
        $jobs = PoneIntegrationOutbox::query()->where('business_id', $businessId)->latest('id')->paginate(100);
        $links = PoneIntegrationLink::query()->where('business_id', $businessId)->latest('synced_at')->limit(100)->get();
        return view('pumperdashboardnew::admin.integration.index', compact('jobs', 'links'));
    }
    public function retry(int $job)
    {
        $job = PoneIntegrationOutbox::query()->whereKey($job)->where('business_id', $this->businessId())->firstOrFail();
        $success = $this->processor->retry($job);
        return $this->ok($success ? __('pumperdashboardnew::lang.integration_retry_succeeded') : __('pumperdashboardnew::lang.integration_retry_failed'));
    }
    public function retryAll()
    {
        $result = $this->processor->processPending($this->businessId(), 500);
        return $this->ok(__('pumperdashboardnew::lang.integration_retry_result', $result), $result);
    }
}
