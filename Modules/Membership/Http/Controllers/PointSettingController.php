<?php

namespace Modules\Membership\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Membership\Http\Controllers\MembershipSettingController;

/**
 * MEM-048 legacy compatibility controller.
 * Keeps old action() calls working and forwards every setting method to MembershipSettingController.
 */
class PointSettingController extends Controller
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


    public function getPointSettings(Request $request) { return $this->callTarget(['getPointSettings', 'dataPointSetting', 'pointSettingData', 'getPointSettingData', 'data'], [$request]); }
    public function data(Request $request) { return $this->getPointSettings($request); }
    public function dataPointSetting(Request $request) { return $this->getPointSettings($request); }

    public function create() { return $this->callTarget(['createPointSetting', 'create']); }
    public function createPointSetting() { return $this->create(); }

    public function store(Request $request) { return $this->callTarget(['storePointSetting', 'store'], [$request]); }
    public function storePointSetting(Request $request) { return $this->store($request); }

    public function view($id) { return $this->callTarget(['showPointSetting', 'viewPointSetting', 'show', 'view'], [$id]); }
    public function show($id) { return $this->view($id); }
    public function showPointSetting($id) { return $this->view($id); }
    public function viewPointSetting($id) { return $this->view($id); }

    public function edit($id) { return $this->callTarget(['editPointSetting', 'edit'], [$id]); }
    public function editPointSetting($id) { return $this->edit($id); }

    public function update(Request $request, $id) { return $this->callTarget(['updatePointSetting', 'update'], [$request, $id]); }
    public function updatePointSetting(Request $request, $id) { return $this->update($request, $id); }

    public function destroy($id) { return $this->callTarget(['destroyPointSetting', 'deletePointSetting', 'destroy', 'delete'], [$id]); }
    public function delete($id) { return $this->destroy($id); }
    public function destroyPointSetting($id) { return $this->destroy($id); }
    public function deletePointSetting($id) { return $this->destroy($id); }

}
