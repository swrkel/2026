<?php
namespace Modules\Purchase\Http\Controllers\Settings;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Settings\SupplierSettingService;
class SupplierSettingController extends Controller
{
    public function index(SupplierSettingService $service)
    {
        return view('purchase::settings.suppliersetting.index', $service->getSettings((int) session('user.business_id')));
    }
}
