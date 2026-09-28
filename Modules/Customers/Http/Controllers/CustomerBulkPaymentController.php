<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Customers\Services\CustomerBulkPaymentService;

class CustomerBulkPaymentController extends Controller
{
    private CustomerBulkPaymentService $service;

    public function __construct(CustomerBulkPaymentService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): View
    {
        $businessId = $this->businessId($request);

        return view('customers::bulk_payments.pos_dashboard', $this->service->formData($businessId));
    }

    public function runtime()
    {
        $path = module_path('Customers', 'Resources/assets/js/bulk-payment.js');
        $javascript = is_file($path) ? file_get_contents($path) : "console.error('Customers Bulk Payment runtime file is missing.');";

        return response($javascript, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            // The view uses a versioned URL, so the static runtime can be cached
            // instead of being downloaded and read from disk on every page load.
            'Cache-Control' => 'public, max-age=86400, immutable',
        ]);
    }

    public function accounts(Request $request, string $group): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'accounts' => $this->service->accountsForGroup($this->businessId($request), $group),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Throwable $e) {
            Log::error('Customers Bulk Payment account loading failed', [
                'business_id' => $this->businessId($request),
                'group' => $group,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load the accounts linked with the selected Payment Method.',
                'accounts' => [],
            ], 500);
        }
    }

    public function customerSummary(Request $request, int $customer): JsonResponse
    {
        try {
            $data = $this->service->customerSummary($this->businessId($request), $customer);

            return response()->json([
                'success' => true,
                'total_due' => $data['total_due'],
                'points' => $data['points'],
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Throwable $e) {
            Log::error('Customers Bulk Payment customer summary loading failed', [
                'business_id' => $this->businessId($request),
                'customer_id' => $customer,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load the selected customer balance.',
            ], 500);
        }
    }

    public function customerInvoices(Request $request, int $customer): JsonResponse
    {
        try {
            $invoices = $this->service->customerInvoices($this->businessId($request), $customer);

            return response()->json([
                'success' => true,
                'invoice_count' => $invoices->count(),
                'invoice_html' => view('customers::bulk_payments.partials.invoice_rows', [
                    'invoices' => $invoices,
                    'interestEnabled' => (bool) $request->boolean('interest_enabled'),
                ])->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Throwable $e) {
            Log::error('Customers Bulk Payment invoice loading failed', [
                'business_id' => $this->businessId($request),
                'customer_id' => $customer,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load the selected customer outstanding bills.',
            ], 500);
        }
    }

    public function customerData(Request $request, int $customer): JsonResponse
    {
        try {
            $data = $this->service->customerData($this->businessId($request), $customer);

            return response()->json([
                'success' => true,
                'total_due' => $data['total_due'],
                'points' => $data['points'],
                'invoice_count' => $data['invoices']->count(),
                'invoice_html' => view('customers::bulk_payments.partials.invoice_rows', [
                    'invoices' => $data['invoices'],
                    'interestEnabled' => (bool) $request->boolean('interest_enabled'),
                ])->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Throwable $e) {
            Log::error('Customers Bulk Payment customer loading failed', [
                'business_id' => $this->businessId($request),
                'customer_id' => $customer,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load the selected customer balance and outstanding bills.',
            ], 500);
        }
    }

    /**
     * Backward-compatible JSON endpoint for old Customer Payment Bulk links.
     */
    public function customerDetails(Request $request, int $customer): JsonResponse
    {
        $data = $this->service->customerData($this->businessId($request), $customer);

        return response()->json([
            'success' => 1,
            'total_outstanding' => $data['total_due'],
            'total_outstanding_amt' => $data['total_due'],
            'points' => $data['points'],
        ]);
    }

    /**
     * Backward-compatible row endpoint. New installations use customerData().
     */
    public function bulkPaymentTable(Request $request)
    {
        $customerId = (int) $request->input('customer_id');
        $invoices = $this->service->customerInvoices($this->businessId($request), $customerId);

        return view('customers::bulk_payments.partials.invoice_rows', [
            'invoices' => $invoices,
            'interestEnabled' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $result = $this->service->save(
                $this->businessId($request),
                $this->userId($request),
                $request->all()
            );

            return redirect()
                ->route('customers.bulk_payment.index')
                ->with('status', [
                    'success' => true,
                    'msg' => 'Bulk Payment ' . $result['reference'] . ' saved successfully.',
                    'receipt_url' => route('customers.bulk_payment.receipt', ['reference' => $result['reference']]),
                ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Customers Bulk Payment failed', [
                'business_id' => $this->businessId($request),
                'user_id' => $this->userId($request),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('status', [
                    'success' => false,
                    'msg' => 'Bulk Payment could not be saved. Please check the entered details and try again.',
                ]);
        }
    }

    public function receipt(Request $request, string $reference): View
    {
        return view(
            'customers::bulk_payments.receipt',
            $this->service->receiptData($this->businessId($request), $reference)
        );
    }

    private function businessId(Request $request): int
    {
        return (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional($request->user())->business_id
        );
    }

    private function userId(Request $request): int
    {
        return (int) (
            $request->session()->get('user.id')
            ?: optional($request->user())->id
        );
    }
}
