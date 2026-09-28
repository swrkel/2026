<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentController extends BaseDealerApiController
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('transaction_payments')) {
            return $this->success(['rows' => []]);
        }

        $query = DB::table('transaction_payments')
            ->where('business_id', $this->businessId($request))
            ->whereNull('deleted_at')
            ->where(function ($q) use ($request) {
                $customerId = $this->customerId($request);
                $businessId = $this->businessId($request);
                $q->where('payment_for', $customerId);
                if (Schema::hasTable('transactions')) {
                    $q->orWhereIn('transaction_id', function ($sub) use ($customerId, $businessId) {
                        $sub->select('id')->from('transactions')
                            ->where('business_id', $businessId)
                            ->where('contact_id', $customerId)
                            ->whereNull('deleted_at');
                    });
                }
            });

        if ($request->filled('from')) {
            $query->whereDate('paid_on', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('paid_on', '<=', $request->get('to'));
        }
        if ($request->filled('search')) {
            $search = '%' . $request->get('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('payment_ref_no', 'like', $search)->orWhere('method', 'like', $search)->orWhere('note', 'like', $search);
            });
        }

        $rows = $query->select(['id', 'paid_on', 'payment_ref_no', 'method', 'amount', 'note', 'transaction_id', 'created_at'])
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->limit($this->paginateLimit($request, 100, 500))
            ->get();

        return $this->success(['rows' => $rows]);
    }

    public function show(Request $request, $id)
    {
        if (!Schema::hasTable('transaction_payments')) {
            return $this->fail('Payment table is not available.', 404);
        }

        $row = DB::table('transaction_payments')
            ->where('business_id', $this->businessId($request))
            ->where('payment_for', $this->customerId($request))
            ->whereNull('deleted_at')
            ->where('id', (int) $id)
            ->first();

        if (empty($row)) {
            return $this->fail('Payment not found.', 404);
        }

        return $this->success(['payment' => $row]);
    }
}
