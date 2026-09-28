<?php

namespace Modules\MyHealthMembers\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthBillingInvoice;
use Modules\MyHealthMembers\Entities\MyHealthBillingService as BillingServiceEntity;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\MyHealthBillingService;
use Modules\MyHealthMembers\Services\MyHealthInvoiceNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthBillingInvoiceController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_billing'), 403);
        $query = MyHealthBillingInvoice::query()->with('member');
        if ($search = trim((string) $request->input('search'))) {
            $query->where('invoice_no', 'like', "%{$search}%")
                ->orWhereHas('member', function ($q) use ($search) {
                    $q->where('myhealth_code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%");
                });
        }
        return view('myhealthmembers::billing.invoices.index', ['invoices' => $query->latest('id')->paginate(25)]);
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        return view('myhealthmembers::billing.invoices.create', [
            'members' => MyHealthMember::query()->orderByDesc('id')->limit(500)->get(),
            'services' => BillingServiceEntity::query()->where('is_active', true)->orderBy('service_name')->get(),
        ]);
    }

    public function store(Request $request, MyHealthInvoiceNumberService $numberService, MyHealthBillingService $billingService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'invoice_date' => ['nullable', 'date'],
            'discount_amount' => ['nullable', 'numeric'],
            'insurance_amount' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);
        $items = collect($request->input('items', []))->filter(fn ($item) => !empty($item['description']) && (float) ($item['qty'] ?? 0) > 0);

        DB::connection(config('myhealthmembers.central_connection'))->transaction(function () use ($data, $items, $numberService, $billingService) {
            $invoice = MyHealthBillingInvoice::create([
                'invoice_no' => $numberService->nextNumber(),
                'member_id' => $data['member_id'],
                'invoice_date' => $data['invoice_date'] ?? date('Y-m-d'),
                'discount_amount' => (float) ($data['discount_amount'] ?? 0),
                'insurance_amount' => (float) ($data['insurance_amount'] ?? 0),
                'status' => 'unpaid',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $discount = (float) ($item['discount_amount'] ?? 0);
                $invoice->items()->create([
                    'service_id' => $item['service_id'] ?? null,
                    'item_type' => $item['item_type'] ?? 'service',
                    'description' => $item['description'],
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'line_total' => max(($qty * $unitPrice) - $discount, 0),
                ]);
            }
            $billingService->refreshInvoiceTotals($invoice);
        });

        return redirect()->route('myhealth.billing.invoices.index')->with('status', __('myhealthmembers::lang.billing_invoice_saved'));
    }

    public function show(MyHealthBillingInvoice $invoice, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_billing'), 403);
        $invoice->load(['member', 'items.service', 'payments']);
        return view('myhealthmembers::billing.invoices.show', compact('invoice'));
    }
}
