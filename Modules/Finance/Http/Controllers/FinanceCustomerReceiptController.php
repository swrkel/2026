<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\BusinessLocation;

class FinanceCustomerReceiptController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session()->get('user.business_id');

        $locations = BusinessLocation::where('business_id', $business_id)
            ->pluck('name', 'location_id');

        $query = DB::table('transaction_payments')
            ->join(
                'transactions',
                'transaction_payments.transaction_id',
                '=',
                'transactions.id'
            )
            ->leftJoin(
                'contacts',
                'transactions.contact_id',
                '=',
                'contacts.id'
            )
            ->leftJoin(
                'business_locations',
                'transactions.location_id',
                '=',
                'business_locations.id'
            )
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->select(
                'transaction_payments.id',
                'transaction_payments.paid_on',
                'transaction_payments.amount',
                'transaction_payments.method',
                'transaction_payments.payment_ref_no',
                'transactions.invoice_no',
                'transactions.location_id',
                'contacts.name as customer_name',
                'business_locations.name as location_name'
            );

        if (!empty($request->location_id) && $request->location_id != 'all') {
            $query->where('transactions.location_id', $request->location_id);
        }

        if (!empty($request->from_date)) {
            $query->whereDate('transaction_payments.paid_on', '>=', $request->from_date);
        }

        if (!empty($request->to_date)) {
            $query->whereDate('transaction_payments.paid_on', '<=', $request->to_date);
        }

        if (!empty($request->customer_name)) {
            $query->where('contacts.name', 'like', '%' . $request->customer_name . '%');
        }

        $receipts = $query
            ->orderBy('transaction_payments.paid_on', 'desc')
            ->paginate(25);

        $total_receipts = $query->sum('transaction_payments.amount');

        return view('finance::customer_receipts.index')
            ->with(compact(
                'receipts',
                'locations',
                'total_receipts'
            ));
    }
}