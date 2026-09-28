<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\OperatorAccessUpdateRequest;
use Modules\PumperDashboardNew\Services\PoneOperatorManagementService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class OperatorController extends Controller
{
    public function __construct(private PoneOperatorManagementService $operators, private PoneSharedMasterDataService $masterData) {}
    public function index()
    {
        $businessId = $this->businessId();
        $operators = $this->operators->listing($businessId);
        $locations = $this->masterData->locations($businessId);
        return view('pumperdashboardnew::admin.operators.index', compact('operators', 'locations'));
    }
    public function sync()
    {
        $count = $this->operators->sync($this->businessId());
        return $this->ok(__('pumperdashboardnew::lang.operators_synced', ['count' => $count]));
    }
    public function update(OperatorAccessUpdateRequest $request, int $operator)
    {
        $this->operators->updateAccess($this->businessId(), $operator, $request->validated());
        return $this->ok(__('pumperdashboardnew::lang.operator_access_updated'));
    }
}
