<?php

namespace Modules\Subscription\Http\Controllers;

use App\Contact;
use App\Services\Documents\GlobalPdfService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Subscription\Entities\SubscriptionBankAccount;
use Modules\Subscription\Entities\SubscriptionBanner;
use Modules\Subscription\Entities\SubscriptionInvoice;
use Modules\Subscription\Entities\SubscriptionInvoiceItem;
use Modules\Subscription\Entities\SubscriptionInvoicePrefix;
use Modules\Subscription\Entities\SubscriptionPaymentTerm;
use Modules\Subscription\Entities\SubscriptionPrice;
use Modules\Subscription\Entities\SubscriptionSetting;
use Modules\Subscription\Entities\SubscriptionUserActivity;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionInvoiceController extends Controller
{
    public function index()
    {
        $business_id = request()->session()->get('business.id');

        if (request()->ajax()) {
            $query = SubscriptionInvoice::leftJoin('contacts', 'contacts.id', '=', 'subscription_invoices.customer_id')
                ->where('subscription_invoices.business_id', $business_id)
                ->select(
                    'subscription_invoices.*',
                    'contacts.name as customer_name'
                )
                ->orderByDesc('subscription_invoices.id');

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $editUrl = route('subscription.invoices.edit', $row->id);
                    $deleteUrl = route('subscription.invoices.destroy', $row->id);

                    return '<a href="' . $editUrl . '" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i></a> '
                        . '<button type="button" class="btn btn-xs btn-danger delete-invoice" data-href="' . $deleteUrl . '"><i class="fa fa-trash"></i></button>';
                })
                ->editColumn('created_at', function ($row) {
                    return !empty($row->created_at) ? $row->created_at->format('Y-m-d H:i:s') : '';
                })
                ->editColumn('total', function ($row) {
                    return number_format((float) $row->total, 2, '.', ',');
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $customers = Contact::customersDropdown($business_id, false);

        return view('subscription::invoices.index', compact('customers'));
    }

    public function create()
    {
        return $this->renderForm();
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('business.id');

        $invoice = SubscriptionInvoice::with('items')
            ->where('business_id', $business_id)
            ->findOrFail($id);

        return $this->renderForm($invoice);
    }

    private function generateInvoiceNumber($business_id)
    {
        $userId = auth()->id();
        $prefixQuery = SubscriptionInvoicePrefix::where('user_id', $userId);
        if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
            $prefixQuery->where('business_id', $business_id);
        }

        $prefixRow = $prefixQuery->first();

        Log::info('Generating invoice number. Prefix row: ', (array) $prefixRow);

        if ($prefixRow) {
            $prefix        = $prefixRow->prefix ?? 'INV-';
            $currentNumber = $prefixRow->current_number ?? 1;

            return $prefix . str_pad($currentNumber, 6, '0', STR_PAD_LEFT);
        }

        return 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    }

    public function store(Request $request)
    {
        return $this->saveInvoice($request);
    }

    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('business.id');

        $invoice = SubscriptionInvoice::where('business_id', $business_id)->findOrFail($id);

        return $this->saveInvoice($request, $invoice);
    }

    public function pdf(Request $request)
    {
        $data = $request->all();

        Log::info('data in pdf method: ', $data);

        // Get customer name
        $customer     = Contact::find($data['customer_id']);
        $customerName = $customer->name ?? '—';

        // Get payment term name
        $paymentTermModel = SubscriptionPaymentTerm::find($data['payment_term_id']);
        $paymentTermName  = $paymentTermModel->name ?? '—';

        // Prepare invoice items
        $items = [];

        foreach ($data['items'] ?? [] as $item) {
            if (! empty($item['description'])) {
                $items[] = [
                    'description' => $item['description'],
                    'cycle'       => $item['cycle'] ?? null,
                    'qty'         => $item['qty'],
                    'price'       => $item['price'],
                    'total'       => $item['qty'] * $item['price'],
                ];
            }
        }

        foreach ($data['manual_items'] ?? [] as $item) {
            if (! empty($item['description'])) {
                $items[] = [
                    'description' => $item['description'],
                    'cycle'       => $item['cycle'] ?? null,
                    'qty'         => $item['qty'],
                    'price'       => $item['price'],
                    'total'       => $item['qty'] * $item['price'],
                ];
            }
        }

        // Get latest banner for this business
        $business_id = session('business.id');

        $banner = SubscriptionBanner::where('business_id', $business_id)
            ->latest()
            ->first();

        $bannerPath = null;

        if ($banner && $banner->file_path) {
            $bannerPath = storage_path('app/public/' . $banner->file_path);
        }

        Log::info('banner path in pdf method: ' . $bannerPath);


        $bankModel = null;

        if (!empty($data['bank_account_id'])) {
            $bankModel = SubscriptionBankAccount::find($data['bank_account_id']);
        }

        $bank = [
            'name'    => $data['bank_name'] ?? ($bankModel->bank ?? '—'),
            'ac_name' => $data['bank_ac_name'] ?? ($bankModel->ac_name ?? '—'),
            'ac_no'   => $data['bank_ac'] ?? ($bankModel->ac_no ?? '—'),
            'branch'  => $data['bank_branch'] ?? ($bankModel->branch ?? '—'),
        ];

        // Prepare invoice object
        $invoice = (object) [
            'invoice_no'       => $data['invoice_no'] ?? '—',
            'customer_name'    => $customerName,
            'customer_code'    => $data['customer_code'] ?? '—',
            'customer_address' => $data['customer_address'] ?? '—',
            'from_date'        => $data['period_from'] ?? '—',
            'to_date'          => $data['period_to'] ?? '—',
            'items'            => $items,
            'total'            => $data['grand_total'] ?? 0,
            'banner_image'     => $bannerPath,
            'bank'             => $bank,
            'payment_term'     => $paymentTermName,
            'payment_method'   => $data['payment_method'] ?? '—',
            'payment_details'  => $data['payment_details'] ?? '—',
        ];

        $html = view('subscription::invoices.pdf', compact('invoice'))->render();

        return app(GlobalPdfService::class)->download(
            $html,
            $invoice->invoice_no . '.pdf',
            ['format' => 'A5', 'orientation' => 'P'],
            [
                'page_title' => 'Subscription Invoice ' . $invoice->invoice_no,
                'date_range' => trim((string) $invoice->from_date) . ' - ' . trim((string) $invoice->to_date),
            ]
        );
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('business.id');
        $invoice = SubscriptionInvoice::where('business_id', $business_id)->findOrFail($id);

        $invoiceNo = $invoice->invoice_no;

        SubscriptionInvoiceItem::where('invoice_id', $invoice->id)->delete();
        $invoice->delete();

        SubscriptionUserActivity::create([
            'business_id' => session('business.id'),
            'model'       => 'SubscriptionInvoice',
            'subject_id'    => $id,
            'description'      => 'deleted',
            'properties'  => 'Deleted Invoice No: ' . $invoiceNo,
            'created_by'  => auth()->id(),
        ]);

        $output = [
            'success' => true,
            'msg' => 'Invoice deleted successfully'
        ];

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with($output);
    }

    private function renderForm(?SubscriptionInvoice $invoice = null)
    {
        $business_id = request()->session()->get('business.id');

        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->select('id', 'name', 'contact_id', 'address_line_1', 'landmark')
            ->get();

        $banner = SubscriptionBanner::where('business_id', $business_id)->latest()->first();
        $tenant = session('tenant.name', 'tenant1');
        $url = $banner && $banner->file_path
            ? route('tenant.storage', ['tenant' => $tenant, 'path' => $banner->file_path])
            : null;

        $subscription_settings = SubscriptionSetting::where('business_id', $business_id)
            ->get()
            ->map(function ($setting) use ($business_id) {
                $latestPrice = SubscriptionPrice::where('settings_id', $setting->id)
                    ->where('business_id', $business_id)
                    ->latest()
                    ->first();

                $product = DB::table('subscription_product')
                    ->where('id', $setting->product)
                    ->select('subscription_product', 'code')
                    ->first();

                $setting->subscription_amount = $latestPrice->new_amount ?? $setting->base_amount;
                $setting->subscription_code = $product->code ?? $setting->product;
                $setting->subscription_product_name = $product->subscription_product ?? $setting->product;

                return $setting;
            });

        $system_items = collect();
        $manual_items = collect();

        if ($invoice) {
            $system_items = $invoice->items->where('source', 'system')->values();
            $manual_items = $invoice->items->where('source', 'manual')->values();
        }

        return view('subscription::invoices.create', [
            'invoice'               => $invoice,
            'banner'                => $banner,
            'customers'             => $customers,
            'subscription_settings' => $subscription_settings,
            'banks'                 => SubscriptionBankAccount::where('business_id', $business_id)->get(),
            'payment_terms'         => SubscriptionPaymentTerm::where('business_id', $business_id)->get(),
            'invoice_no'            => $invoice->invoice_no ?? $this->generateInvoiceNumber($business_id),
            'banner_image'          => $url,
            'system_items'          => $system_items,
            'manual_items'          => $manual_items,
        ]);
    }

    private function saveInvoice(Request $request, ?SubscriptionInvoice $invoice = null)
    {
        DB::beginTransaction();

        try {
            $business_id = request()->session()->get('business.id');
            $isUpdate = !empty($invoice);

            $validated = $request->validate([
                'invoice_no' => 'required|string|max:255',
                'customer_id' => 'required|integer|exists:contacts,id',
                'customer_code' => 'nullable|string|max:255',
                'customer_address' => 'nullable|string',
                'period_from' => 'required|date',
                'period_to' => 'required|date|after_or_equal:period_from',
                'bank_account_id' => 'nullable|integer',
                'payment_term_id' => 'nullable|integer',
                'banner_id' => 'nullable|integer',
                'payment_method' => 'nullable|string|max:255',
                'payment_details' => 'nullable|string',
                'grand_total' => 'required|numeric|min:0',
            ]);

            if (!$invoice) {
                $invoice = new SubscriptionInvoice();
                $invoice->business_id = $business_id;
                $invoice->user_id = auth()->id();
            }

            $invoice->fill([
                'invoice_no' => $validated['invoice_no'],
                'customer_id' => $validated['customer_id'],
                'customer_code' => $validated['customer_code'] ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'from_date' => $validated['period_from'],
                'to_date' => $validated['period_to'],
                'bank_account_id' => $validated['bank_account_id'] ?? null,
                'payment_term_id' => $validated['payment_term_id'] ?? null,
                'banner_id' => $validated['banner_id'] ?? null,
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_details' => $validated['payment_details'] ?? null,
                'total' => $validated['grand_total'],
            ]);
            $invoice->save();

            SubscriptionInvoiceItem::where('invoice_id', $invoice->id)->delete();

            foreach ($request->items ?? [] as $item) {
                if (!empty($item['description'])) {
                    SubscriptionInvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'setting_id' => !empty($item['setting_id']) ? (int) $item['setting_id'] : null,
                        'description' => $item['description'],
                        'cycle' => $item['cycle'] ?? null,
                        'qty' => $item['qty'] ?? 1,
                        'price' => $item['price'] ?? 0,
                        'total' => ($item['qty'] ?? 0) * ($item['price'] ?? 0),
                        'source' => 'system',
                    ]);
                }
            }

            foreach ($request->manual_items ?? [] as $item) {
                if (!empty($item['description'])) {
                    SubscriptionInvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                        'cycle' => $item['cycle'] ?? null,
                        'qty' => $item['qty'] ?? 1,
                        'price' => $item['price'] ?? 0,
                        'total' => ($item['qty'] ?? 0) * ($item['price'] ?? 0),
                        'source' => 'manual',
                    ]);
                }
            }

            if (!$isUpdate) {
                $prefixQuery = SubscriptionInvoicePrefix::where('user_id', auth()->id());
                if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
                    $prefixQuery->where('business_id', $business_id);
                }
                $prefixQuery->increment('current_number');
            }

            DB::commit();

            SubscriptionUserActivity::create([
                'business_id' => $business_id,
                'model' => 'SubscriptionInvoice',
                'description' => $isUpdate ? 'updated' : 'created',
                'subject_id' => $invoice->id,
                'properties' => 'Invoice No: ' . $invoice->invoice_no,
                'created_by' => auth()->id(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'msg' => $isUpdate ? __('messages.updated_successfully') : __('messages.saved_successfully'),
                    'redirect' => route('subscription.invoices.index'),
                ]);
            }

            return redirect()
                ->route('subscription.invoices.index')
                ->with('success', $isUpdate ? __('messages.updated_successfully') : __('messages.saved_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('messages.something_went_wrong'));
        }
    }
}
