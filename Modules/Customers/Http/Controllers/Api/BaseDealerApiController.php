<?php

namespace Modules\Customers\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;

class BaseDealerApiController extends Controller
{
    protected function businessId(Request $request): int
    {
        return (int) $request->attributes->get('dealer_business_id', 0);
    }

    protected function customerId(Request $request): int
    {
        return (int) $request->attributes->get('dealer_customer_id', 0);
    }

    protected function customer(Request $request): ?Customer
    {
        return $request->attributes->get('dealer_customer');
    }

    protected function success($data = [], string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function fail(string $message, int $status = 422, $data = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function portalSummary(int $businessId, int $customerId): array
    {
        $invoiceTotal = 0.0;
        $paymentTotal = 0.0;
        $outstanding = 0.0;
        $lastPaymentDate = null;
        $creditLimit = 0.0;

        if (Schema::hasTable('contacts')) {
            $creditLimit = (float) DB::table('contacts')
                ->where('business_id', $businessId)
                ->where('id', $customerId)
                ->value('credit_limit');
        }

        if (Schema::hasTable('transactions')) {
            $invoiceTotal = (float) DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereIn('type', ['sell', 'opening_balance', 'direct_customer_loan', 'security_deposit'])
                ->whereNull('deleted_at')
                ->sum('final_total');
        }

        if (Schema::hasTable('transaction_payments')) {
            $paymentQuery = DB::table('transaction_payments')
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->where(function ($query) use ($customerId, $businessId) {
                    $query->where('payment_for', $customerId);

                    if (Schema::hasTable('transactions')) {
                        $query->orWhereIn('transaction_id', function ($sub) use ($customerId, $businessId) {
                            $sub->select('id')
                                ->from('transactions')
                                ->where('business_id', $businessId)
                                ->where('contact_id', $customerId)
                                ->whereNull('deleted_at');
                        });
                    }
                });

            $paymentTotal = (float) (clone $paymentQuery)->sum('amount');
            $lastPaymentDate = (clone $paymentQuery)->orderByDesc('paid_on')->value('paid_on');
        }

        if (Schema::hasTable('contact_ledgers')) {
            $ledgerBalance = DB::table('contact_ledgers')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereNull('deleted_at')
                ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END) as balance")
                ->value('balance');

            $outstanding = $ledgerBalance !== null ? (float) $ledgerBalance : ($invoiceTotal - $paymentTotal);
        } else {
            $outstanding = $invoiceTotal - $paymentTotal;
        }

        return [
            'invoice_total' => round($invoiceTotal, 2),
            'payment_total' => round($paymentTotal, 2),
            'outstanding' => round($outstanding, 2),
            'credit_limit' => round($creditLimit, 2),
            'available_credit' => round(max($creditLimit - $outstanding, 0), 2),
            'last_payment_date' => $lastPaymentDate ? date('Y-m-d', strtotime($lastPaymentDate)) : null,
        ];
    }

    protected function paginateLimit(Request $request, int $default = 50, int $max = 200): int
    {
        $limit = (int) $request->get('limit', $default);
        if ($limit <= 0) {
            $limit = $default;
        }
        return min($limit, $max);
    }
}
