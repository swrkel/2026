<?php
namespace Modules\Purchase\Http\Controllers\Settings;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Settings\GeneralSettingService;
class GeneralSettingController extends Controller
{
    public function index(GeneralSettingService $service)
    {
        return view('purchase::settings.generalsetting.index', $service->getSettings((int) session('user.business_id')));
    }
}
