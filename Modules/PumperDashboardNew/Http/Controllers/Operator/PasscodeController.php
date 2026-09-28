<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\PasscodeUpdateRequest;
use Modules\PumperDashboardNew\Services\PonePasscodeService;
class PasscodeController extends Controller
{
    public function __construct(private PonePasscodeService $passcodes){}
    public function edit(){return view('pumperdashboardnew::operator.settings.passcode');}
    public function update(PasscodeUpdateRequest $request){$this->passcodes->update($request->validated('current_passcode'),$request->validated('new_passcode'));return $this->ok(__('pumperdashboardnew::lang.passcode_updated'));}
}
