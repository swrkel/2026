<?php
namespace Modules\Purchase\Http\Controllers\Settings;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Settings\NumberingService;
class NumberingController extends Controller
{
    public function index(NumberingService $service)
    {
        return view('purchase::settings.numbering.index', $service->getSettings((int) session('user.business_id')));
    }
}
