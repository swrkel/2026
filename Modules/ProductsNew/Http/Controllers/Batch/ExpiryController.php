<?php
namespace Modules\ProductsNew\Http\Controllers\Batch;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Batch\ExpiryAlertService;

class ExpiryController extends Controller
{
    public function __construct(protected ExpiryAlertService $service) {}
    public function index(Request $request)
    {
        $alerts = $this->service->query($request->all())->paginate(50);
        return view('productsnew::batch.expiry', compact('alerts'));
    }
    public function refresh(Request $request)
    {
        $count = $this->service->refresh($request->business_id, (int)$request->input('days',30));
        return back()->with('status', __('productsnew::product.expiry_alerts_refreshed', ['count'=>$count]));
    }
    public function resolve(int $alert)
    {
        $this->service->resolve($alert);
        return back()->with('status', __('productsnew::product.expiry_alert_resolved'));
    }
}
