<?php
namespace Modules\Purchase\Http\Controllers\Settings;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Settings\TaxService;
class TaxController extends Controller
{
    public function index(TaxService $service)
    {
        return view('purchase::settings.tax.index', $service->getSettings((int) session('user.business_id')));
    }
}
