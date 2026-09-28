<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoiceController extends BaseDealerApiController
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('transactions')) {
            return $this->success(['rows' => []]);
        }

        $query = DB::table('transactions')
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request))
            ->whereIn('type', ['sell', 'opening_balance', 'direct_customer_loan', 'security_deposit'])
            ->whereNull('deleted_at');

        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->get('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->get('to'));
        }
        if ($request->filled('search')) {
            $search = '%' . $request->get('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', $search)->orWhere('ref_no', 'like', $search);
            });
        }

        $rows = $query->select([
                'id', 'transaction_date', 'invoice_no', 'ref_no', 'type', 'payment_status', 'final_total', 'created_at'
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($this->paginateLimit($request, 100, 500))
            ->get();

        return $this->success(['rows' => $rows]);
    }

    public function show(Request $request, $id)
    {
        if (!Schema::hasTable('transactions')) {
            return $this->fail('Invoice table is not available.', 404);
        }

        $row = DB::table('transactions')
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request))
            ->whereNull('deleted_at')
            ->where('id', (int) $id)
            ->first();

        if (empty($row)) {
            return $this->fail('Invoice not found.', 404);
        }

        return $this->success(['invoice' => $row]);
    }
}
