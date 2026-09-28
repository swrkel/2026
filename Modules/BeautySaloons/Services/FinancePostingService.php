<?php
namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyFinancePosting;

class FinancePostingService
{
    public function postSale(array $sale): void
    {
        DB::transaction(function () use ($sale) {
            $ref = $sale['invoice_no'] ?? ('BS-SALE-' . now()->format('YmdHis'));
            $date = $sale['transaction_date'] ?? now()->toDateString();
            $businessId = $sale['business_id'] ?? null;
            $locationId = $sale['business_location_id'] ?? null;

            $this->line($businessId, $locationId, $date, $ref, 'sale', 'cash_account', $sale['paid_amount'] ?? 0, 0, 'Beauty sale collection');
            $this->line($businessId, $locationId, $date, $ref, 'sale', 'service_income', 0, $sale['service_total'] ?? 0, 'Beauty service income');
            $this->line($businessId, $locationId, $date, $ref, 'sale', 'product_income', 0, $sale['product_total'] ?? 0, 'Beauty product income');
            if (!empty($sale['discount_amount'])) {
                $this->line($businessId, $locationId, $date, $ref, 'sale', 'discount_allowed', $sale['discount_amount'], 0, 'Beauty discount allowed');
            }
        });
    }

    public function postWalletTopUp(array $tx): void
    {
        $ref = $tx['reference_no'] ?? ('BS-WALLET-' . now()->format('YmdHis'));
        $this->line($tx['business_id'] ?? null, $tx['business_location_id'] ?? null, $tx['transaction_date'] ?? now()->toDateString(), $ref, 'wallet', 'cash_account', $tx['amount'] ?? 0, 0, 'Wallet top-up collection');
        $this->line($tx['business_id'] ?? null, $tx['business_location_id'] ?? null, $tx['transaction_date'] ?? now()->toDateString(), $ref, 'wallet', 'wallet_liability', 0, $tx['amount'] ?? 0, 'Wallet liability');
    }

    public function postVoucherSale(array $tx): void
    {
        $ref = $tx['voucher_no'] ?? ('BS-VOUCHER-' . now()->format('YmdHis'));
        $this->line($tx['business_id'] ?? null, $tx['business_location_id'] ?? null, $tx['transaction_date'] ?? now()->toDateString(), $ref, 'voucher', 'cash_account', $tx['amount'] ?? 0, 0, 'Voucher sale collection');
        $this->line($tx['business_id'] ?? null, $tx['business_location_id'] ?? null, $tx['transaction_date'] ?? now()->toDateString(), $ref, 'voucher', 'voucher_liability', 0, $tx['amount'] ?? 0, 'Voucher liability');
    }

    private function line($businessId, $locationId, $date, $ref, $type, $accountKey, $debit, $credit, $note): void
    {
        if ((float)$debit == 0.0 && (float)$credit == 0.0) return;
        BeautyFinancePosting::create([
            'business_id' => $businessId,
            'business_location_id' => $locationId,
            'posting_date' => $date,
            'reference_no' => $ref,
            'posting_type' => $type,
            'account_key' => $accountKey,
            'debit' => $debit,
            'credit' => $credit,
            'note' => $note,
            'status' => 'posted',
        ]);
    }
}
