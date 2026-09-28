<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Admin;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\AssignmentStoreRequest;
use Modules\PumperDashboardNew\Http\Requests\AssignmentUpdateRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneAssignmentManagementService;
class AssignmentController extends Controller
{
    public function __construct(private PoneAssignmentManagementService $assignments){}
    public function store(AssignmentStoreRequest $request,int $shift){$shift=$this->shift($shift);$this->assignments->add($shift,$request->validated(),(int)auth()->id());return $this->ok(__('pumperdashboardnew::lang.assignment_added'));}
    public function update(AssignmentUpdateRequest $request,int $assignment){$assignment=$this->assignment($assignment);$this->assignments->update($assignment,$request->validated(),(int)auth()->id());return $this->ok(__('pumperdashboardnew::lang.assignment_updated'));}
    public function destroy(VoidRequest $request,int $assignment){$assignment=$this->assignment($assignment);$this->assignments->cancel($assignment,$request->validated('reason'),(int)auth()->id());return $this->ok(__('pumperdashboardnew::lang.assignment_cancelled'));}
    private function shift(int $id):PoneShift{return PoneShift::query()->whereKey($id)->where('business_id',$this->businessId())->firstOrFail();}
    private function assignment(int $id):PonePumpAssignment{return PonePumpAssignment::query()->whereKey($id)->where('business_id',$this->businessId())->firstOrFail();}
}
