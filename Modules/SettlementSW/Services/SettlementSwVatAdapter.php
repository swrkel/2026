<?php

namespace Modules\SettlementSW\Services;

use Illuminate\Support\Facades\DB;

/**
 * Module-local adapter for optional VAT actions used by Settlement SW list pages.
 *
 * Controllers must not reference the VAT module directly. This adapter keeps the
 * integration optional and configurable, so Settlement SW remains standalone while
 * preserving existing behaviour when the VAT module exists in the host ERP.
 */
class SettlementSwVatAdapter
{
    public function shouldShowRegenerateAction($moduleUtil, $transactionUtil, int $businessId, $row, $settlement): bool
    {
        if (empty($settlement) || empty($settlement->id)) {
            return false;
        }

        if (! method_exists($moduleUtil, 'hasThePermissionInSubscription') ||
            ! $moduleUtil->hasThePermissionInSubscription($businessId, config('settlementsw.vat.subscription_permission_key', 'individual_sale'))
        ) {
            return false;
        }

        if (! method_exists($transactionUtil, '__getVatEffectiveDate')) {
            return false;
        }

        return strtotime($transactionUtil->__getVatEffectiveDate($businessId)) <= strtotime($row->transaction_date);
    }

    public function findSaleTransactionBySettlementNo(string $settlementNo)
    {
        return DB::table(config('settlementsw.vat.transaction_table', 'transactions'))
            ->where('invoice_no', $settlementNo)
            ->where('type', config('settlementsw.vat.sale_transaction_type', 'sell'))
            ->first();
    }

    public function regenerateUrl(int $transactionId): ?string
    {
        $action = config('settlementsw.vat.regenerate_action');

        if (empty($action) || ! $this->actionTargetExists($action)) {
            return null;
        }

        return action($action, ['transaction_id' => $transactionId]);
    }

    public function regenerateLabel(): string
    {
        return __('settlementsw::lang.regenerate_vat');
    }

    private function actionTargetExists(string $action): bool
    {
        if (! str_contains($action, '@')) {
            return false;
        }

        [$controller, $method] = explode('@', ltrim($action, '\\'), 2);

        return class_exists($controller) && method_exists($controller, $method);
    }
}
