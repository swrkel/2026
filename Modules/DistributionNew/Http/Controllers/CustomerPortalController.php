<?php
namespace Modules\DistributionNew\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\CustomerPortal\DisnewCustomerOrderAccessService;
use Modules\DistributionNew\Services\Orders\DisnewSalesOrderWorkflowService;
class CustomerPortalController extends Controller
{
    public function dashboard(Request $request){ return view('distributionnew::customer_portal.dashboard'); }
    public function orders(Request $request){ return view('distributionnew::customer_portal.orders'); }
    public function storeOrder(Request $request, DisnewSalesOrderWorkflowService $workflow)
    {
        $payload = $request->all();
        $payload['created_by_type'] = 'customer_portal';
        $workflow->createFromPortal($payload);
        return redirect()->route('distribution-new.customer-portal.orders')->with('status', __('distributionnew::messages.order_created_successfully'));
    }
}
