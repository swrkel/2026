<?php

namespace Modules\Membership\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Membership\Http\Controllers\MembershipSettingController;

/**
 * MEM-048 legacy compatibility controller.
 * Keeps old action() calls working and forwards every setting method to MembershipSettingController.
 */
class BusinessTypeController extends Controller
{
    protected function target()
    {
        return app(MembershipSettingController::class);
    }

    protected function callTarget(array $methods, array $args = [])
    {
        $target = $this->target();
        foreach ($methods as $method) {
            if (method_exists($target, $method)) {
                return call_user_func_array([$target, $method], $args);
            }
        }

        if (request()->ajax()) {
            return response()->json(['success' => false, 'msg' => 'Membership setting action is not available.', 'data' => []], 404);
        }

        abort(404, 'Membership setting action is not available.');
    }


    public function getBusinessTypes(Request $request) { return $this->callTarget(['getBusinessTypes', 'dataBusinessType', 'businessTypeData', 'getBusinessTypeData', 'data'], [$request]); }
    public function data(Request $request) { return $this->getBusinessTypes($request); }
    public function dataBusinessType(Request $request) { return $this->getBusinessTypes($request); }

    public function getUsersByBusinessType($id) { return $this->callTarget(['getUsersByBusinessType'], [$id]); }

    public function create() { return $this->callTarget(['createBusinessType', 'create']); }
    public function createBusinessType() { return $this->create(); }

    public function store(Request $request) { return $this->callTarget(['storeBusinessType', 'store'], [$request]); }
    public function storeBusinessType(Request $request) { return $this->store($request); }

    public function view($id) { return $this->callTarget(['viewBusinessType', 'showBusinessType', 'show', 'view'], [$id]); }
    public function show($id) { return $this->view($id); }
    public function viewBusinessType($id) { return $this->view($id); }
    public function showBusinessType($id) { return $this->view($id); }

    public function edit($id) { return $this->callTarget(['editBusinessType', 'edit'], [$id]); }
    public function editBusinessType($id) { return $this->edit($id); }

    public function update(Request $request, $id) { return $this->callTarget(['updateBusinessType', 'update'], [$request, $id]); }
    public function updateBusinessType(Request $request, $id) { return $this->update($request, $id); }

    public function disable($id) { return $this->callTarget(['disableBusinessType', 'disable'], [$id]); }
    public function disableBusinessType($id) { return $this->disable($id); }

    public function destroy($id) { return $this->callTarget(['destroyBusinessType', 'deleteBusinessType', 'destroy', 'delete'], [$id]); }
    public function delete($id) { return $this->destroy($id); }
    public function destroyBusinessType($id) { return $this->destroy($id); }
    public function deleteBusinessType($id) { return $this->destroy($id); }

}
