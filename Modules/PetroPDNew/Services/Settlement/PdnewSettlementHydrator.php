<?php

namespace Modules\PetroPDNew\Services\Settlement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PetroPDNew\Entities\PdnewSettlement;
use Modules\PetroPDNew\Entities\PdnewSettlementCollection;
use Modules\PetroPDNew\Entities\PdnewSettlementCommission;
use Modules\PetroPDNew\Entities\PdnewSettlementCreditSale;
use Modules\PetroPDNew\Entities\PdnewSettlementCreditSaleLine;
use Modules\PetroPDNew\Entities\PdnewSettlementDayEntry;
use Modules\PetroPDNew\Entities\PdnewSettlementLedgerEntry;
use Modules\PetroPDNew\Entities\PdnewSettlementMeterSale;
use Modules\PetroPDNew\Entities\PdnewSettlementOtherSale;
use Modules\PetroPDNew\Entities\PdnewSettlementOtherSaleLine;
use Modules\PetroPDNew\Entities\PdnewSettlementPayment;
use Modules\PetroPDNew\Entities\PdnewSettlementPaymentDetail;
use Modules\PetroPDNew\Entities\PdnewSettlementPump;
use Modules\PetroPDNew\Entities\PdnewSettlementRecovery;
use Modules\PetroPDNew\Entities\PdnewSettlementSource;
use Modules\PetroPDNew\Entities\PdnewSettlementUnloadStock;
use Modules\PetroPDNew\Entities\PdnewSettlementUnloadStockLine;

class PdnewSettlementHydrator
{
    public function hydrate(PdnewSettlement $settlement, array $snapshot): void
    {
        DB::transaction(function () use ($settlement, $snapshot): void {
            $this->clearSourceRows($settlement);

            $businessId = (int) $settlement->business_id;
            $shift = (array) ($snapshot['shift'] ?? []);

            PdnewSettlementSource::query()->create([
                'business_id' => $businessId,
                'settlement_id' => $settlement->id,
                'pone_shift_id' => (int) $shift['id'],
                'pone_shift_number' => (string) $shift['shift_number'],
                'source_hash' => (string) $snapshot['source_hash'],
                'source_closed_at' => $shift['closed_at'] ?? null,
                'source_totals' => [
                    'meter_sales_total' => $shift['meter_sales_total'] ?? 0,
                    'other_sales_total' => $shift['other_sales_total'] ?? 0,
                    'payments_total' => $shift['payments_total'] ?? 0,
                    'declared_total' => $shift['declared_total'] ?? 0,
                    'expected_total' => $shift['expected_total'] ?? 0,
                    'shortage_amount' => $shift['shortage_amount'] ?? 0,
                    'excess_amount' => $shift['excess_amount'] ?? 0,
                    'reconciliation_status' => $shift['reconciliation_status'] ?? 'pending',
                ],
            ]);

            foreach ((array) ($snapshot['assignments'] ?? []) as $assignment) {
                if (($assignment['status'] ?? '') === 'cancelled') continue;

                PdnewSettlementPump::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_assignment_id' => (int) $assignment['id'],
                    'pump_id' => (int) $assignment['pump_id'],
                    'product_id' => (int) ($assignment['product_id'] ?? 0) ?: null,
                    'opening_meter' => $assignment['opening_meter'] ?? 0,
                    'closing_meter' => $assignment['closing_meter'] ?? $assignment['current_meter'] ?? 0,
                    'testing_quantity' => $assignment['testing_quantity'] ?? 0,
                    'sold_quantity' => $assignment['sold_quantity'] ?? 0,
                    'unit_price' => $assignment['unit_price'] ?? 0,
                    'amount' => $assignment['amount'] ?? 0,
                    'status' => (string) ($assignment['status'] ?? 'closed'),
                ]);

                $lastReading = $this->lastReadingFor(
                    (array) ($snapshot['meter_readings'] ?? []),
                    (int) $assignment['id']
                );

                PdnewSettlementMeterSale::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_assignment_id' => (int) $assignment['id'],
                    'pone_meter_reading_id' => $lastReading['id'] ?? null,
                    'pump_id' => (int) $assignment['pump_id'],
                    'product_id' => (int) ($assignment['product_id'] ?? 0) ?: null,
                    'reading_at' => $lastReading['recorded_at'] ?? $assignment['closed_at'] ?? $shift['closed_at'] ?? null,
                    'opening_meter' => $assignment['opening_meter'] ?? 0,
                    'closing_meter' => $assignment['closing_meter'] ?? $assignment['current_meter'] ?? 0,
                    'testing_quantity' => $assignment['testing_quantity'] ?? 0,
                    'sold_quantity' => $assignment['sold_quantity'] ?? 0,
                    'unit_price' => $assignment['unit_price'] ?? 0,
                    'amount' => $assignment['amount'] ?? 0,
                ]);
            }

            foreach ((array) ($snapshot['payments'] ?? []) as $sourcePayment) {
                if (in_array((string) ($sourcePayment['status'] ?? ''), ['void', 'cancelled'], true)) continue;

                $payment = PdnewSettlementPayment::query()->create([
                    'uuid' => (string) ($sourcePayment['uuid'] ?? Str::uuid()),
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_payment_id' => (int) $sourcePayment['id'],
                    'payment_number' => (string) ($sourcePayment['payment_number'] ?? ('PONE-' . $sourcePayment['id'])),
                    'payment_type' => (string) ($sourcePayment['payment_type'] ?? 'cash'),
                    'gross_amount' => $sourcePayment['gross_amount'] ?? $sourcePayment['amount'] ?? 0,
                    'discount_amount' => $sourcePayment['discount_amount'] ?? 0,
                    'amount' => $sourcePayment['amount'] ?? 0,
                    'customer_id' => (int) ($sourcePayment['customer_id'] ?? 0) ?: null,
                    'reference_no' => $sourcePayment['reference_no'] ?? $sourcePayment['slip_no'] ?? $sourcePayment['cheque_no'] ?? null,
                    'transaction_at' => $sourcePayment['transaction_at'] ?? $shift['closed_at'] ?? null,
                    'status' => 'active',
                    'is_source' => true,
                    'note' => $sourcePayment['note'] ?? null,
                    'metadata' => [
                        'included_in_received_total' => ! in_array(
                            (string) ($sourcePayment['payment_type'] ?? ''),
                            ['shortage', 'excess'],
                            true
                        ),
                        'source_classification' => in_array(
                            (string) ($sourcePayment['payment_type'] ?? ''),
                            ['shortage', 'excess'],
                            true
                        ) ? 'operational_variance' : 'collection',
                        'card_type' => $sourcePayment['card_type'] ?? null,
                        'card_last_four' => $sourcePayment['card_last_four'] ?? null,
                        'slip_no' => $sourcePayment['slip_no'] ?? null,
                        'bank_name' => $sourcePayment['bank_name'] ?? null,
                        'cheque_no' => $sourcePayment['cheque_no'] ?? null,
                        'cheque_date' => $sourcePayment['cheque_date'] ?? null,
                    ],
                    'created_by' => $sourcePayment['created_by'] ?? null,
                ]);

                foreach ((array) ($sourcePayment['cash_denominations'] ?? []) as $detail) {
                    PdnewSettlementPaymentDetail::query()->create([
                        'business_id' => $businessId,
                        'settlement_id' => $settlement->id,
                        'payment_id' => $payment->id,
                        'detail_type' => 'cash_denomination',
                        'reference_no' => (string) ($detail['denomination'] ?? ''),
                        'amount' => $detail['amount'] ?? 0,
                        'detail' => $detail,
                    ]);
                }

                foreach ((array) ($sourcePayment['card_lines'] ?? []) as $detail) {
                    PdnewSettlementPaymentDetail::query()->create([
                        'business_id' => $businessId,
                        'settlement_id' => $settlement->id,
                        'payment_id' => $payment->id,
                        'detail_type' => 'card',
                        'reference_no' => $detail['slip_no'] ?? $detail['reference_no'] ?? null,
                        'amount' => $detail['amount'] ?? 0,
                        'detail' => $detail,
                    ]);
                }

                $credit = $sourcePayment['credit_sale'] ?? null;
                if (is_array($credit)) {
                    $creditModel = PdnewSettlementCreditSale::query()->create([
                        'business_id' => $businessId,
                        'settlement_id' => $settlement->id,
                        'pone_credit_sale_id' => (int) $credit['id'],
                        'pone_payment_id' => (int) $sourcePayment['id'],
                        'customer_id' => (int) ($credit['customer_id'] ?? 0) ?: null,
                        'order_number' => $credit['order_number'] ?? null,
                        'bill_number' => $credit['bill_number'] ?? null,
                        'vehicle_number' => $credit['vehicle_number'] ?? null,
                        'customer_reference' => $credit['customer_reference'] ?? null,
                        'order_date' => $credit['order_date'] ?? null,
                        'due_date' => $credit['due_date'] ?? null,
                        'amount' => $sourcePayment['amount'] ?? 0,
                        'confirmed' => ! empty($credit['locked_at']) || (
                            ! empty($credit['customer_confirmed'])
                            && ! empty($credit['order_confirmed'])
                            && ! empty($credit['vehicle_confirmed'])
                        ),
                        'metadata' => [
                            'confirmation_rounds' => $credit['confirmation_rounds'] ?? 0,
                            'locked_at' => $credit['locked_at'] ?? null,
                        ],
                    ]);

                    foreach ((array) ($credit['lines'] ?? []) as $line) {
                        PdnewSettlementCreditSaleLine::query()->create([
                            'business_id' => $businessId,
                            'settlement_id' => $settlement->id,
                            'credit_sale_id' => $creditModel->id,
                            'pone_credit_sale_line_id' => (int) $line['id'],
                            'product_id' => (int) ($line['product_id'] ?? 0) ?: null,
                            'quantity' => $line['quantity'] ?? 0,
                            'unit_price' => $line['unit_price'] ?? 0,
                            'discount_amount' => $line['discount_amount'] ?? 0,
                            'amount' => $line['amount'] ?? 0,
                        ]);
                    }
                }
            }

            foreach ((array) ($snapshot['other_sales'] ?? []) as $sourceSale) {
                if (in_array((string) ($sourceSale['status'] ?? ''), ['void', 'cancelled'], true)) continue;

                $sale = PdnewSettlementOtherSale::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_other_sale_id' => (int) $sourceSale['id'],
                    'sale_number' => (string) $sourceSale['sale_number'],
                    'store_id' => (int) ($sourceSale['store_id'] ?? 0) ?: null,
                    'sale_at' => $sourceSale['sale_at'] ?? null,
                    'gross_amount' => $sourceSale['gross_amount'] ?? 0,
                    'discount_amount' => $sourceSale['discount_amount'] ?? 0,
                    'net_amount' => $sourceSale['net_amount'] ?? 0,
                    'status' => (string) ($sourceSale['status'] ?? 'active'),
                ]);

                foreach ((array) ($sourceSale['lines'] ?? []) as $line) {
                    PdnewSettlementOtherSaleLine::query()->create([
                        'business_id' => $businessId,
                        'settlement_id' => $settlement->id,
                        'other_sale_id' => $sale->id,
                        'pone_other_sale_line_id' => (int) $line['id'],
                        'product_id' => (int) ($line['product_id'] ?? 0) ?: null,
                        'quantity' => $line['quantity'] ?? 0,
                        'unit_price' => $line['unit_price'] ?? 0,
                        'discount_amount' => $line['discount_amount'] ?? 0,
                        'amount' => $line['amount'] ?? 0,
                    ]);
                }
            }

            foreach ((array) ($snapshot['unload_stocks'] ?? []) as $sourceUnload) {
                if (in_array((string) ($sourceUnload['status'] ?? ''), ['void', 'cancelled'], true)) continue;

                $unload = PdnewSettlementUnloadStock::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_unload_stock_id' => (int) $sourceUnload['id'],
                    'receipt_number' => (string) $sourceUnload['receipt_number'],
                    'bill_number' => $sourceUnload['bill_number'] ?? null,
                    'store_id' => (int) ($sourceUnload['store_id'] ?? 0) ?: null,
                    'unloaded_at' => $sourceUnload['unloaded_at'] ?? null,
                    'total_quantity' => $sourceUnload['total_quantity'] ?? 0,
                    'total_amount' => $sourceUnload['total_amount'] ?? 0,
                    'status' => (string) ($sourceUnload['status'] ?? 'active'),
                ]);

                foreach ((array) ($sourceUnload['lines'] ?? []) as $line) {
                    PdnewSettlementUnloadStockLine::query()->create([
                        'business_id' => $businessId,
                        'settlement_id' => $settlement->id,
                        'unload_stock_id' => $unload->id,
                        'pone_unload_stock_line_id' => (int) $line['id'],
                        'product_id' => (int) ($line['product_id'] ?? 0) ?: null,
                        'tank_id' => (int) ($line['tank_id'] ?? 0) ?: null,
                        'quantity' => $line['quantity'] ?? 0,
                        'unit_cost' => $line['unit_cost'] ?? 0,
                        'amount' => $line['amount'] ?? 0,
                        'dip_reading' => $line['dip_reading'] ?? 0,
                        'current_stock' => $line['current_stock'] ?? 0,
                    ]);
                }
            }

            foreach ((array) ($snapshot['day_entries'] ?? []) as $entry) {
                if (in_array((string) ($entry['status'] ?? ''), ['void', 'cancelled'], true)) continue;
                PdnewSettlementDayEntry::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_day_entry_id' => (int) $entry['id'],
                    'entry_type' => (string) ($entry['entry_type'] ?? 'entry'),
                    'reference_no' => $entry['reference_no'] ?? null,
                    'pump_id' => (int) ($entry['pump_id'] ?? 0) ?: null,
                    'assignment_id' => (int) ($entry['assignment_id'] ?? 0) ?: null,
                    'quantity' => $entry['quantity'] ?? 0,
                    'amount' => $entry['amount'] ?? 0,
                    'starting_meter' => $entry['starting_meter'] ?? 0,
                    'closing_meter' => $entry['closing_meter'] ?? 0,
                    'testing_quantity' => $entry['testing_quantity'] ?? 0,
                    'entry_at' => $entry['entry_at'] ?? null,
                    'status' => (string) ($entry['status'] ?? 'active'),
                    'note' => $entry['note'] ?? null,
                ]);
            }

            foreach ((array) ($snapshot['collections'] ?? []) as $collection) {
                if (in_array((string) ($collection['status'] ?? ''), ['void', 'cancelled'], true)) continue;
                PdnewSettlementCollection::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_collection_id' => (int) $collection['id'],
                    'collection_number' => (string) $collection['collection_number'],
                    'collection_at' => $collection['collection_at'] ?? null,
                    'expected_amount' => $collection['expected_amount'] ?? 0,
                    'cash_amount' => $collection['cash_amount'] ?? 0,
                    'card_amount' => $collection['card_amount'] ?? 0,
                    'cheque_amount' => $collection['cheque_amount'] ?? 0,
                    'credit_amount' => $collection['credit_amount'] ?? 0,
                    'other_amount' => $collection['other_amount'] ?? 0,
                    'declared_amount' => $collection['declared_amount'] ?? 0,
                    'difference_amount' => $collection['difference_amount'] ?? 0,
                    'status' => (string) ($collection['status'] ?? 'confirmed'),
                ]);
            }

            foreach ((array) ($snapshot['ledger_entries'] ?? []) as $entry) {
                PdnewSettlementLedgerEntry::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_ledger_entry_id' => (int) $entry['id'],
                    'entry_at' => $entry['entry_at'] ?? null,
                    'source_type' => (string) ($entry['source_type'] ?? 'unknown'),
                    'source_id' => (int) ($entry['source_id'] ?? 0) ?: null,
                    'reference_no' => $entry['reference_no'] ?? null,
                    'description' => $entry['description'] ?? null,
                    'debit' => $entry['debit'] ?? 0,
                    'credit' => $entry['credit'] ?? 0,
                    'status' => (string) ($entry['status'] ?? 'active'),
                ]);
            }

            foreach ((array) ($snapshot['shortage_recoveries'] ?? []) as $row) {
                if (in_array((string) ($row['status'] ?? ''), ['void', 'cancelled'], true)) continue;
                PdnewSettlementRecovery::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_recovery_id' => (int) $row['id'],
                    'recovery_number' => (string) $row['recovery_number'],
                    'recovery_date' => $row['recovery_date'] ?? null,
                    'amount' => $row['amount'] ?? 0,
                    'payment_method' => $row['payment_method'] ?? null,
                    'reference_no' => $row['reference_no'] ?? null,
                    'status' => (string) ($row['status'] ?? 'active'),
                    'note' => $row['note'] ?? null,
                ]);
            }

            foreach ((array) ($snapshot['excess_commissions'] ?? []) as $row) {
                if (in_array((string) ($row['status'] ?? ''), ['void', 'cancelled'], true)) continue;
                PdnewSettlementCommission::query()->create([
                    'business_id' => $businessId,
                    'settlement_id' => $settlement->id,
                    'pone_commission_id' => (int) $row['id'],
                    'commission_number' => (string) $row['commission_number'],
                    'commission_date' => $row['commission_date'] ?? null,
                    'base_excess_amount' => $row['base_excess_amount'] ?? 0,
                    'commission_type' => $row['commission_type'] ?? null,
                    'commission_rate' => $row['commission_rate'] ?? 0,
                    'commission_amount' => $row['commission_amount'] ?? 0,
                    'status' => (string) ($row['status'] ?? 'active'),
                    'note' => $row['note'] ?? null,
                ]);
            }
        }, 3);
    }

    private function clearSourceRows(PdnewSettlement $settlement): void
    {
        $settlementId = (int) $settlement->id;

        $sourcePaymentIds = PdnewSettlementPayment::query()
            ->where('settlement_id', $settlementId)
            ->where('is_source', true)
            ->pluck('id');

        if ($sourcePaymentIds->isNotEmpty()) {
            PdnewSettlementPaymentDetail::query()->whereIn('payment_id', $sourcePaymentIds)->delete();
            PdnewSettlementPayment::query()->whereIn('id', $sourcePaymentIds)->delete();
        }

        $creditIds = PdnewSettlementCreditSale::query()->where('settlement_id', $settlementId)->pluck('id');
        if ($creditIds->isNotEmpty()) {
            PdnewSettlementCreditSaleLine::query()->whereIn('credit_sale_id', $creditIds)->delete();
            PdnewSettlementCreditSale::query()->whereIn('id', $creditIds)->delete();
        }

        $saleIds = PdnewSettlementOtherSale::query()->where('settlement_id', $settlementId)->pluck('id');
        if ($saleIds->isNotEmpty()) {
            PdnewSettlementOtherSaleLine::query()->whereIn('other_sale_id', $saleIds)->delete();
            PdnewSettlementOtherSale::query()->whereIn('id', $saleIds)->delete();
        }

        $unloadIds = PdnewSettlementUnloadStock::query()->where('settlement_id', $settlementId)->pluck('id');
        if ($unloadIds->isNotEmpty()) {
            PdnewSettlementUnloadStockLine::query()->whereIn('unload_stock_id', $unloadIds)->delete();
            PdnewSettlementUnloadStock::query()->whereIn('id', $unloadIds)->delete();
        }

        foreach ([
            PdnewSettlementSource::class,
            PdnewSettlementPump::class,
            PdnewSettlementMeterSale::class,
            PdnewSettlementDayEntry::class,
            PdnewSettlementCollection::class,
            PdnewSettlementLedgerEntry::class,
            PdnewSettlementRecovery::class,
            PdnewSettlementCommission::class,
        ] as $model) {
            $model::query()->where('settlement_id', $settlementId)->delete();
        }
    }

    private function lastReadingFor(array $readings, int $assignmentId): array
    {
        $found = array_values(array_filter(
            $readings,
            fn (array $row): bool => (int) ($row['assignment_id'] ?? 0) === $assignmentId
        ));

        usort($found, fn (array $a, array $b): int => (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0));
        return $found === [] ? [] : end($found);
    }
}
