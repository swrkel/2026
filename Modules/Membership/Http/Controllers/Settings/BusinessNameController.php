<?php

namespace Modules\Membership\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Membership\Http\Controllers\MembershipSettingController;

/**
 * MEM-048 legacy compatibility controller.
 * Keeps old action() calls working and forwards every setting method to MembershipSettingController.
 */
class BusinessNameController extends Controller
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


    public function getBusinessNames(Request $request) { return $this->callTarget(['getBusinessNames', 'dataBusinessName', 'businessNameData', 'getBusinessNameData', 'data'], [$request]); }
    public function data(Request $request) { return $this->getBusinessNames($request); }
    public function dataBusinessName(Request $request) { return $this->getBusinessNames($request); }

    public function create() { return $this->callTarget(['createBusinessName', 'create']); }
    public function createBusinessName() { return $this->create(); }

    public function store(Request $request) { return $this->callTarget(['storeBusinessName', 'store'], [$request]); }
    public function storeBusinessName(Request $request) { return $this->store($request); }

    public function view($id) { return $this->callTarget(['viewBusinessName', 'showBusinessName', 'show', 'view'], [$id]); }
    public function show($id) { return $this->view($id); }
    public function viewBusinessName($id) { return $this->view($id); }
    public function showBusinessName($id) { return $this->view($id); }

    public function edit($id) { return $this->callTarget(['editBusinessName', 'edit'], [$id]); }
    public function editBusinessName($id) { return $this->edit($id); }

    public function update(Request $request, $id) { return $this->callTarget(['updateBusinessName', 'update'], [$request, $id]); }
    public function updateBusinessName(Request $request, $id) { return $this->update($request, $id); }

    public function destroy($id) { return $this->callTarget(['destroyBusinessName', 'deleteBusinessName', 'destroy', 'delete'], [$id]); }
    public function delete($id) { return $this->destroy($id); }
    public function destroyBusinessName($id) { return $this->destroy($id); }
    public function deleteBusinessName($id) { return $this->destroy($id); }

}
