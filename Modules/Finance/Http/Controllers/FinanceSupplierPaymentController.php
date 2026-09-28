<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Entities\BusinessLocation;

class FinanceSupplierPaymentController extends Controller
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
            ->where('transactions.type', 'purchase')
            ->select(
                'transaction_payments.id',
                'transaction_payments.paid_on',
                'transaction_payments.amount',
                'transaction_payments.method',
                'transaction_payments.payment_ref_no',
                'transactions.ref_no',
                'transactions.location_id',
                'contacts.name as supplier_name',
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

        if (!empty($request->supplier_name)) {
            $query->where('contacts.name', 'like', '%' . $request->supplier_name . '%');
        }

        $total_supplier_payments = $query->sum('transaction_payments.amount');

        $supplier_payments = $query
            ->orderBy('transaction_payments.paid_on', 'desc')
            ->paginate(25);

        return view('finance::supplier_payments.index')
            ->with(compact(
                'supplier_payments',
                'locations',
                'total_supplier_payments'
            ));
    }
}