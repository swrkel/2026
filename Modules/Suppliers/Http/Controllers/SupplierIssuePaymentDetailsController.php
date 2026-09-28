<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Utils\SupplierContextUtil;

class SupplierIssuePaymentDetailsController extends Controller
{
    public function index(Request $request)
    {
        $businessId = SupplierContextUtil::businessId();

        $payments = collect();

        if (Schema::hasTable('transaction_payments') && Schema::hasTable('contacts')) {
            $query = DB::table('transaction_payments')
                ->leftJoin('contacts', function ($join) {
                    $join->on('transaction_payments.payment_for', '=', 'contacts.id');
                })
                ->where(function ($q) use ($businessId) {
                    if (Schema::hasColumn('transaction_payments', 'business_id')) {
                        $q->where('transaction_payments.business_id', $businessId)
                          ->orWhereNull('transaction_payments.business_id');
                    } else {
                        $q->where('contacts.business_id', $businessId);
                    }
                })
                ->where('contacts.business_id', $businessId)
                ->where(function ($q) {
                    $q->where('contacts.type', 'supplier')
                      ->orWhere('contacts.type', 'both');
                });

            if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
                $query->whereNull('transaction_payments.deleted_at');
            }

            // Supplier Pay Due creates allocation children. They carry the same
            // SLP reference as the parent but are not separate issued payments.
            // Show the parent/root payment once only.
            if (Schema::hasColumn('transaction_payments', 'parent_id')) {
                $query->whereNull('transaction_payments.parent_id');
            }

            if (Schema::hasColumn('contacts', 'deleted_at')) {
                $query->whereNull('contacts.deleted_at');
            }

            $payments = $query->select([
                    'transaction_payments.id',
                    'transaction_payments.paid_on',
                    'transaction_payments.method',
                    'transaction_payments.amount',
                    'transaction_payments.payment_ref_no',
                    'contacts.name as supplier_name',
                    'contacts.supplier_business_name',
                    'contacts.contact_id as supplier_code',
                ])
                ->orderByDesc('transaction_payments.paid_on')
                ->orderByDesc('transaction_payments.id')
                ->limit(500)
                ->get();
        }

        return view('suppliers::issues.payment_details', compact('payments'));
    }
}
