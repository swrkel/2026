<?php
namespace Modules\Purchase\Http\Controllers\Settings;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Settings\ApprovalService;
class ApprovalController extends Controller
{
    public function index(ApprovalService $service)
    {
        return view('purchase::settings.approval.index', $service->getSettings((int) session('user.business_id')));
    }
}
