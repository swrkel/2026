<?php

namespace Modules\SettlementSW\Http\Controllers;

use Illuminate\Http\Request;
use Modules\SettlementSW\Entities\SettlementCreditSalePayment;

/**
 * Focused Settlement SW credit-sale payment controller.
 */
class SettlementSwAddPaymentCreditSaleController extends SettlementSwAddPaymentBaseController
{
    /**
     * Validate voucher/order-number uniqueness without exposing another module.
     */
    public function check_order_number(Request $request)
    {
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id'));
        $orderNumber = trim((string) $request->input('order_number', $request->input('voucher_no', '')));

        if ($orderNumber === '') {
            return response()->json([
                'success' => false,
                'exists' => false,
                'available' => false,
                'msg' => __('validation.required', ['attribute' => __('settlementsw::lang.order_number')]),
            ], 422);
        }

        $exists = SettlementCreditSalePayment::query()
            ->where('business_id', $businessId)
            ->where('order_number', $orderNumber)
            ->when($request->filled('id'), function ($query) use ($request) {
                $query->where('id', '!=', (int) $request->input('id'));
            })
            ->exists();

        return response()->json([
            'success' => true,
            'exists' => $exists,
            'available' => ! $exists,
            'msg' => $exists
                ? __('settlementsw::lang.order_number') . ' already exists.'
                : '',
        ]);
    }
}
