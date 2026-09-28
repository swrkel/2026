<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSCustomerService;

class CustomerController extends Controller
{
    public function __construct(private POSCustomerService $customers) {}

    public function index(Request $request)
    {
        $title = 'POS Customers';
        $customers = $this->customers->list($request);
        $stats = $this->customers->stats();
        return view('pos::customers.index', compact('title','customers','stats'));
    }

    public function create()
    {
        $title = 'Add POS Customer';
        $customer = null;
        return view('pos::customers.form', compact('title','customer'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $id = $this->customers->create($data);
        return redirect()->route('pos.customers.show', $id)->with('status','Customer saved successfully.');
    }

    public function show($customer)
    {
        $title = 'POS Customer Ledger';
        $customer = $this->customers->find((int)$customer);
        abort_if(!$customer, 404);
        $rows = $this->customers->ledgerRows((int)$customer->id);
        return view('pos::customers.show', compact('title','customer','rows'));
    }

    public function edit($customer)
    {
        $title = 'Edit POS Customer';
        $customer = $this->customers->find((int)$customer);
        abort_if(!$customer, 404);
        return view('pos::customers.form', compact('title','customer'));
    }

    public function update(Request $request, $customer)
    {
        $this->customers->update((int)$customer, $this->validated($request));
        return redirect()->route('pos.customers.show', $customer)->with('status','Customer updated successfully.');
    }

    public function payment(Request $request, $customer)
    {
        $data = $request->validate(['amount'=>'required|numeric|min:0.01','reference_no'=>'nullable|string|max:191','note'=>'nullable|string|max:500']);
        $this->customers->receivePayment((int)$customer, (float)$data['amount'], $data['reference_no'] ?? null, $data['note'] ?? null);
        return back()->with('status','Customer payment recorded successfully.');
    }

    public function statement($customer)
    {
        $title = 'POS Customer Statement';
        $data = $this->customers->statement((int)$customer);
        abort_if(!$data['customer'], 404);
        return view('pos::customers.statement', ['title'=>$title] + $data);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_code'=>'nullable|string|max:50','name'=>'required|string|max:191','mobile'=>'nullable|string|max:50',
            'email'=>'nullable|email|max:191','nic_no'=>'nullable|string|max:100','address'=>'nullable|string|max:500',
            'customer_type'=>'required|in:walk_in,credit,loyalty','credit_limit'=>'nullable|numeric|min:0','opening_balance'=>'nullable|numeric|min:0',
            'status'=>'required|in:active,inactive','note'=>'nullable|string|max:500',
        ]);
    }
}
