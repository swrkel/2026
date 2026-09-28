<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Services\CustomerLedgerService;
use Modules\Customers\Services\CustomerPortalOrderService;

class CustomerPortalOrderController extends Controller
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

    protected function requireCustomer(Request $request): Customer
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            abort(redirect()->route('customers.portal.login'));
        }
        return $customer;
    }

    protected function portalContext(Request $request, CustomerLedgerService $ledgerService): array
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return [null, 0, null];
        }

        $businessId = $this->businessId($request);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);

        return [$customer, $businessId, $summary];
    }

    public function index(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $rows = $orderService->dealerOrders($businessId, (int) $customer->id, 1000);

        return view('customers::portal.orders', compact('customer', 'summary', 'rows'));
    }

    public function products(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $rows = $orderService->productRows($businessId, $request->get('search'), 500);

        return view('customers::portal.products', compact('customer', 'summary', 'rows'));
    }

    public function create(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $products = $orderService->productRows($businessId, null, 1000);

        return view('customers::portal.place_order', compact('customer', 'summary', 'products'));
    }

    public function store(Request $request, CustomerPortalOrderService $orderService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);

        if (!Schema::hasTable('customer_portal_orders') || !Schema::hasTable('customer_portal_order_lines')) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Customer portal order tables are missing. Please run the CUS-024 SQL first.',
            ]);
        }

        $data = $request->validate([
            'required_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:2000',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|integer',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|numeric|min:0.0001',
            'line_remarks' => 'nullable|array',
            'line_remarks.*' => 'nullable|string|max:1000',
        ]);

        [$lines, $totalQty, $totalAmount] = $orderService->buildOrderLines(
            $businessId,
            array_values($data['product_id']),
            array_values($data['quantity']),
            array_values($data['line_remarks'] ?? [])
        );

        if (count($lines) === 0) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Please add at least one valid product with quantity.',
            ]);
        }

        DB::beginTransaction();
        try {
            $orderId = DB::table('customer_portal_orders')->insertGetId([
                'business_id' => $businessId,
                'contact_id' => (int) $customer->id,
                'order_no' => $orderService->nextOrderNo($businessId),
                'order_date' => date('Y-m-d'),
                'required_date' => $data['required_date'] ?? null,
                'status' => 'submitted',
                'remarks' => $data['remarks'] ?? null,
                'total_qty' => $totalQty,
                'total_amount' => $totalAmount,
                'created_by_customer' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($lines as &$line) {
                $line['customer_portal_order_id'] = $orderId;
            }
            unset($line);

            DB::table('customer_portal_order_lines')->insert($lines);
            $orderService->recordOrderEvent($businessId, (int) $customer->id, (int) $orderId, 'submitted', 'Order submitted by dealer.');
            DB::commit();

            return redirect()->route('customers.portal.orders.show', $orderId)->with('status', [
                'success' => 1,
                'msg' => 'Order submitted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CUS-024 dealer order submit failed', ['error' => $e->getMessage()]);

            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Unable to submit order. Please try again.',
            ]);
        }
    }

    public function show(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService, $id)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        [$order, $lines] = $orderService->orderWithLines($businessId, (int) $customer->id, (int) $id);
        if (empty($order)) {
            abort(404);
        }

        return view('customers::portal.order_show', compact('customer', 'summary', 'order', 'lines'));
    }

    public function print(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService, $id)
    {
        return $this->show($request, $ledgerService, $orderService, $id);
    }

    public function workflow(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService, $id)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        [$order, $lines] = $orderService->orderWithLines($businessId, (int) $customer->id, (int) $id);
        if (empty($order)) {
            abort(404);
        }

        $events = $orderService->orderEvents($businessId, (int) $customer->id, (int) $id, $order);

        return view('customers::portal.order_workflow', compact('customer', 'summary', 'order', 'lines', 'events'));
    }

    public function repeat(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService, $id)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        [$order, $repeatLines] = $orderService->orderWithLines($businessId, (int) $customer->id, (int) $id);
        if (empty($order)) {
            abort(404);
        }

        $products = $orderService->productRows($businessId, null, 1000);

        return view('customers::portal.order_repeat', compact('customer', 'summary', 'order', 'repeatLines', 'products'));
    }

    public function amend(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService, $id)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        [$order, $lines] = $orderService->orderWithLines($businessId, (int) $customer->id, (int) $id);
        if (empty($order)) {
            abort(404);
        }

        if (!$orderService->orderCanBeAmended($order)) {
            return redirect()->route('customers.portal.orders.show', $order->id)->with('status', [
                'success' => 0,
                'msg' => 'This order cannot be amended after approval or processing has started.',
            ]);
        }

        return view('customers::portal.order_amend', compact('customer', 'summary', 'order', 'lines'));
    }

    public function storeAmendment(Request $request, CustomerPortalOrderService $orderService, $id)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        [$order, $lines] = $orderService->orderWithLines($businessId, (int) $customer->id, (int) $id);

        if (empty($order)) {
            abort(404);
        }

        if (!$orderService->orderCanBeAmended($order)) {
            return redirect()->route('customers.portal.orders.show', $order->id)->with('status', [
                'success' => 0,
                'msg' => 'This order cannot be amended after approval or processing has started.',
            ]);
        }

        $data = $request->validate([
            'requested_changes' => 'required|string|max:5000',
            'reason' => 'nullable|string|max:2000',
        ]);

        if (!Schema::hasTable('customer_portal_order_amendments')) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Order amendment table is missing. Please run the CUS-033 SQL first.',
            ]);
        }

        DB::table('customer_portal_order_amendments')->insert([
            'business_id' => $businessId,
            'contact_id' => (int) $customer->id,
            'customer_portal_order_id' => (int) $order->id,
            'status' => 'submitted',
            'requested_changes' => $data['requested_changes'],
            'reason' => $data['reason'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderService->recordOrderEvent($businessId, (int) $customer->id, (int) $order->id, 'amendment_requested', 'Dealer requested order amendment.');

        return redirect()->route('customers.portal.orders.workflow', $order->id)->with('status', [
            'success' => 1,
            'msg' => 'Order amendment request submitted successfully.',
        ]);
    }

    public function cancel(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService, $id)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        [$order, $lines] = $orderService->orderWithLines($businessId, (int) $customer->id, (int) $id);
        if (empty($order)) {
            abort(404);
        }

        if (!$orderService->orderCanBeCancelled($order)) {
            return redirect()->route('customers.portal.orders.show', $order->id)->with('status', [
                'success' => 0,
                'msg' => 'This order cannot be cancelled at the current stage.',
            ]);
        }

        return view('customers::portal.order_cancel', compact('customer', 'summary', 'order', 'lines'));
    }

    public function storeCancellation(Request $request, CustomerPortalOrderService $orderService, $id)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        [$order, $lines] = $orderService->orderWithLines($businessId, (int) $customer->id, (int) $id);

        if (empty($order)) {
            abort(404);
        }

        if (!$orderService->orderCanBeCancelled($order)) {
            return redirect()->route('customers.portal.orders.show', $order->id)->with('status', [
                'success' => 0,
                'msg' => 'This order cannot be cancelled at the current stage.',
            ]);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:3000',
        ]);

        if (!Schema::hasTable('customer_portal_order_cancellations')) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Order cancellation table is missing. Please run the CUS-033 SQL first.',
            ]);
        }

        DB::beginTransaction();
        try {
            DB::table('customer_portal_order_cancellations')->insert([
                'business_id' => $businessId,
                'contact_id' => (int) $customer->id,
                'customer_portal_order_id' => (int) $order->id,
                'status' => 'submitted',
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('customer_portal_orders')) {
                DB::table('customer_portal_orders')
                    ->where('business_id', $businessId)
                    ->where('contact_id', (int) $customer->id)
                    ->where('id', (int) $order->id)
                    ->update([
                        'status' => 'cancellation_requested',
                        'updated_at' => now(),
                    ]);
            }

            $orderService->recordOrderEvent($businessId, (int) $customer->id, (int) $order->id, 'cancellation_requested', 'Dealer requested order cancellation.');
            DB::commit();

            return redirect()->route('customers.portal.orders.workflow', $order->id)->with('status', [
                'success' => 1,
                'msg' => 'Order cancellation request submitted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('CUS-033 dealer order cancellation failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Unable to submit cancellation request. Please try again.',
            ]);
        }
    }

    public function favourites(Request $request, CustomerLedgerService $ledgerService, CustomerPortalOrderService $orderService)
    {
        [$customer, $businessId, $summary] = $this->portalContext($request, $ledgerService);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $products = $orderService->productRows($businessId, $request->get('search'), 1000);
        $favourites = $orderService->favouriteProductIds($businessId, (int) $customer->id);

        return view('customers::portal.order_favourites', compact('customer', 'summary', 'products', 'favourites'));
    }

    public function storeFavourite(Request $request, CustomerPortalOrderService $orderService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $data = $request->validate([
            'product_id' => 'required|integer',
        ]);

        if (!Schema::hasTable('customer_portal_favourite_products')) {
            return back()->with('status', [
                'success' => 0,
                'msg' => 'Favourite products table is missing. Please run the CUS-033 SQL first.',
            ]);
        }

        $exists = DB::table('customer_portal_favourite_products')
            ->where('business_id', $businessId)
            ->where('contact_id', (int) $customer->id)
            ->where('product_id', (int) $data['product_id'])
            ->exists();

        if (!$exists) {
            DB::table('customer_portal_favourite_products')->insert([
                'business_id' => $businessId,
                'contact_id' => (int) $customer->id,
                'product_id' => (int) $data['product_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('status', [
            'success' => 1,
            'msg' => 'Favourite product saved.',
        ]);
    }

    public function removeFavourite(Request $request, CustomerPortalOrderService $orderService, $productId)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);

        if (Schema::hasTable('customer_portal_favourite_products')) {
            DB::table('customer_portal_favourite_products')
                ->where('business_id', $businessId)
                ->where('contact_id', (int) $customer->id)
                ->where('product_id', (int) $productId)
                ->delete();
        }

        return back()->with('status', [
            'success' => 1,
            'msg' => 'Favourite product removed.',
        ]);
    }
}
