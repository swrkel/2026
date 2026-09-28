<?php

namespace Modules\ProductsNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Deployment\TenantRolloutService;
use Modules\ProductsNew\Services\Deployment\RollbackGuideService;

class DeploymentReadinessController extends Controller
{
    public function index(TenantRolloutService $rollout, RollbackGuideService $rollback)
    {
        return view('productsnew::admin.deployment.index', [
            'status' => $rollout->currentTenantStatus(),
            'checklist' => $rollout->acceptanceChecklist(),
            'rollback_steps' => $rollback->steps(),
        ]);
    }
}
