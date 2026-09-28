<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

use Illuminate\Support\Collection;

class PdSettlementPostingPayload
{
    public function __construct(
        public readonly int $businessId,
        public readonly int $settlementId,
        public readonly string $settlementNo,
        public readonly int $shiftId,
        public readonly string $shiftNumber,
        public readonly int $pumpOperatorId,
        public readonly ?int $locationId,
        public readonly ?string $transactionDate,
        public readonly Collection $meterSales,
        public readonly Collection $payments,
        public readonly Collection $otherSales,
        public readonly array $totals,
        public readonly ?int $createdBy = null,
    ) {}

    public static function fromLoadedSettlement(array $loaded, int $businessId, int $settlementId, string $settlementNo, ?int $createdBy = null): self
    {
        $shift = $loaded['shift'] ?? null;
        $firstAssignment = ($loaded['assignments'] ?? collect())->first();

        return new self(
            businessId: $businessId,
            settlementId: $settlementId,
            settlementNo: $settlementNo,
            shiftId: (int) ($loaded['shift_id'] ?? 0),
            shiftNumber: (string) ($loaded['shift_number'] ?? ''),
            pumpOperatorId: (int) ($loaded['pump_operator_id'] ?? 0),
            locationId: $firstAssignment->location_id ?? null,
            transactionDate: isset($shift->shift_date) ? (string) $shift->shift_date : now()->toDateString(),
            meterSales: $loaded['meter_sales'] ?? collect(),
            payments: $loaded['payments'] ?? collect(),
            otherSales: $loaded['other_sales'] ?? collect(),
            totals: $loaded['totals'] ?? [],
            createdBy: $createdBy,
        );
    }
}
