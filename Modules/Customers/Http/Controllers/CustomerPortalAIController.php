<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Services\CustomerDealerAIService;

class CustomerPortalAIController extends Controller
{
    protected function businessId(Request $request): int
    {
        $businessId = (int) $request->session()->get('distribution_dealer_business_id');
        if ($businessId > 0) {
            return $businessId;
        }

        $businessId = (int) $request->session()->get('user.business_id');
        if ($businessId > 0) {
            return $businessId;
        }

        if (Schema::hasTable('business')) {
            return (int) DB::table('business')->orderBy('id')->value('id');
        }

        return 0;
    }

    protected function loggedCustomer(Request $request): ?Customer
    {
        $businessId = $this->businessId($request);
        $customerId = (int) $request->session()->get('distribution_dealer_customer_id');

        if (empty($businessId) || empty($customerId)) {
            return null;
        }

        return Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->find($customerId);
    }

    public function index(Request $request, CustomerDealerAIService $aiService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $dashboard = $aiService->dashboard($businessId, $customer);
        $response = null;

        if ($request->filled('question')) {
            $response = $aiService->answer($businessId, $customer, (string) $request->question);
        }

        return view('customers::portal.ai_assistant', compact('customer', 'dashboard', 'response'));
    }

    public function ask(Request $request, CustomerDealerAIService $aiService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return response()->json([
                'success' => false,
                'msg' => 'Distribution Dealer session expired. Please login again.',
            ], 401);
        }

        $data = $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $businessId = $this->businessId($request);
        $answer = $aiService->answer($businessId, $customer, $data['question']);

        return response()->json([
            'success' => true,
            'data' => $answer,
        ]);
    }
}
