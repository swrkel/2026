<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\{PaddyPurchase,Setting};

class PaddyPurchaseService
{
    public function __construct(
        private NumberSeriesService $numbers,
        private PurchasePaymentService $payments
    ) {}

    public function create(int $businessId, int $userId, array $data): PaddyPurchase
    {
        return DB::transaction(function () use ($businessId, $userId, $data) {
            $lines = $data['lines'] ?? [];
            $payment = [
                'payment_method' => $data['payment_method'] ?? null,
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'cheque_number' => $data['cheque_number'] ?? null,
                'payment_note' => $data['payment_note'] ?? null,
            ];
            unset($data['lines'], $data['payment_method'], $data['payment_account_id'], $data['cheque_number'], $data['payment_note']);

            $settings = (array) optional(
                Setting::forBusiness($businessId)->select(['id','business_id','settings'])->first()
            )->settings;
            $opening = max(1, (int) ($settings['paddy_purchase_opening_number'] ?? 1));
            $prefix = 'PD-PUR-';

            $purchase = PaddyPurchase::create(array_merge($data, [
                'business_id' => $businessId,
                'purchase_no' => $this->numbers->next($businessId, 'paddy_purchase', $prefix, $opening),
                'status' => 'draft',
                'created_by' => $userId,
            ]));

            $total = 0.0;
            $now = now();
            $insertRows = [];
            foreach ($lines as $line) {
                $qty = (float) ($line['net_weight'] ?? 0);
                $rate = (float) ($line['unit_rate'] ?? 0);
                $ded = (float) ($line['deduction_amount'] ?? 0);
                $amount = max(0, $qty * $rate - $ded);
                $total += $amount;
                $insertRows[] = [
                    'business_id' => $businessId,
                    'purchase_id' => $purchase->id,
                    'paddy_variety_id' => (int) $line['paddy_variety_id'],
                    'net_weight' => $qty,
                    'unit_rate' => $rate,
                    'deduction_amount' => $ded,
                    'line_total' => $amount,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($insertRows) {
                DB::table('rcm_paddy_purchase_lines')->insert($insertRows);
            }

            $purchase->update([
                'subtotal' => $total,
                'net_total' => $total + (float) ($data['other_charges'] ?? 0),
            ]);

            // Save the Purchase Order and its payment mirror in one DB transaction.
            // PurchasePaymentService delegates to the ERP's standard Purchase
            // TransactionPayment / account-event flow; no standalone Finance rows
            // are written by Rice Mill.
            $this->payments->post($businessId, $userId, $purchase, $payment);

            // The controller only needs purchase_no after create. Avoid the old
            // fresh('lines') reload (purchase query + line query) on every save.
            return $purchase;
        });
    }

    public function approve(int $businessId, int $id, int $userId): PaddyPurchase
    {
        return DB::transaction(function () use ($businessId, $id, $userId) {
            $p = PaddyPurchase::forBusiness($businessId)->lockForUpdate()->findOrFail($id);
            if ($p->status !== 'draft') {
                throw new \RuntimeException('Only draft purchases can be approved.');
            }
            $p->update(['status'=>'approved','approved_by'=>$userId,'approved_at'=>now()]);

            // Purchase Order approval is not the actual purchase/receipt. Do not
            // create a second supplier-payable Finance event here. The standard
            // Purchase payment adapter has already saved any real payment made
            // against the order; payable/tax recognition is deferred until the
            // future actual purchase/receipt step.
            return $p;
        });
    }
}
