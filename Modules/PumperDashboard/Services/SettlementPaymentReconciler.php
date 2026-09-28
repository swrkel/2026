<?php

namespace Modules\PumperDashboard\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\PumperDashboard\Entities\SettlementCardPayment;
use Modules\PumperDashboard\Entities\SettlementCashPayment;
use Modules\PumperDashboard\Entities\SettlementChequePayment;
use Modules\PumperDashboard\Entities\SettlementCreditSalePayment;

/**
 * Standalone Pumper Dashboard source-payment reconciler.
 *
 * pump_payment_id is the immutable financial identity. settlement_no may move
 * from NULL to a PD settlement number, but that lifecycle change must update
 * the same row and must never create another payment header.
 */
class SettlementPaymentReconciler
{
    private const TABLE_TO_MODEL = [
        'settlement_card_payments' => SettlementCardPayment::class,
        'settlement_cash_payments' => SettlementCashPayment::class,
        'settlement_cheque_payments' => SettlementChequePayment::class,
        'settlement_credit_sale_payments' => SettlementCreditSalePayment::class,
    ];

    private const TABLE_DEFAULT_IDENTITY = [
        'settlement_card_payments' => 'pump_payment_id',
        'settlement_cash_payments' => 'pump_payment_id',
        'settlement_cheque_payments' => 'pump_payment_id',
        'settlement_credit_sale_payments' => 'pump_payment_id',
    ];

    public function upsertOne(
        int $businessId,
        ?string $settlementNo,
        string $table,
        array $row,
        ?string $identityKey = null
    ): Model {
        if (! isset(self::TABLE_TO_MODEL[$table])) {
            throw new \InvalidArgumentException("Unsupported Pumper Dashboard settlement table: {$table}");
        }

        $identityKey = $identityKey ?? self::TABLE_DEFAULT_IDENTITY[$table];
        if ($identityKey !== 'pump_payment_id') {
            throw new \InvalidArgumentException('Pumper Dashboard writes require pump_payment_id identity.');
        }

        $identityValue = $row[$identityKey] ?? null;
        if (empty($identityValue)) {
            throw new \InvalidArgumentException('Pumper Dashboard payment detail requires pump_payment_id.');
        }

        $modelClass = self::TABLE_TO_MODEL[$table];

        return DB::transaction(function () use (
            $businessId,
            $settlementNo,
            $table,
            $row,
            $identityKey,
            $identityValue,
            $modelClass
        ) {
            $payload = array_merge($row, [
                'business_id' => $businessId,
                'settlement_no' => $settlementNo,
            ]);

            $candidates = $modelClass::where('business_id', $businessId)
                ->where($identityKey, $identityValue)
                ->lockForUpdate()
                ->limit(2)
                ->get();

            if ($candidates->count() > 1) {
                throw new \RuntimeException(
                    "Duplicate {$table} rows already exist for pump_payment_id={$identityValue}."
                );
            }

            $existing = $candidates->first();
            if ($existing) {
                $existingSettlementNo = $existing->settlement_no;
                $hasExistingSettlement = $existingSettlementNo !== null && $existingSettlementNo !== '';
                $hasIncomingSettlement = $settlementNo !== null && $settlementNo !== '';

                if ($hasExistingSettlement
                    && $hasIncomingSettlement
                    && (string) $existingSettlementNo !== (string) $settlementNo) {
                    throw new \RuntimeException(
                        'The Pump Operator Payment is already linked to a different settlement.'
                    );
                }

                if (! $hasIncomingSettlement && $hasExistingSettlement) {
                    $payload['settlement_no'] = $existingSettlementNo;
                }

                $existing->fill($payload);
                $existing->save();
                return $existing;
            }

            return $modelClass::create($payload);
        });
    }
}
