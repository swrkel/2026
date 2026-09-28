<?php
namespace Modules\Audit\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Audit\Models\AuditFinding;
use Modules\Audit\Services\ResolutionService;

class ResolutionController extends Controller
{
    public function update(AuditFinding $finding, ResolutionService $service)
    {
        request()->validate(['status'=>'required|string','note'=>'nullable|string|max:2000']);
        $service->changeStatus($finding,request('status'),request('note'),auth()->id());
        return back()->with('success','Finding status updated.');
    }
}
