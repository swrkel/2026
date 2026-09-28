<?php

namespace Modules\PetroPD\Services\PdSettlementRewrite;

use Modules\PetroPD\Repositories\PdSettlementRewrite\PdSettlementRepository;

class PdSettlementReadService
{
    public function __construct(
        protected PdSettlementRepository $repository,
        protected PdSettlementTotalsService $totalsService,
        protected PdSettlementMeterSaleService $meterSaleService
    ) {
    }

    public function buildDisplayData(int $businessId, int $settlementId): array
    {
        $settlement = $this->repository->findSettlement($businessId, $settlementId);
        if (!$settlement) {
            return [];
        }

        $shiftIds = $this->repository->shiftIdsForSettlement($settlement);
        $paymentCollections = [];

        foreach (PdSettlementTotalsService::PAYMENT_TABLES as $key => $table) {
            $paymentCollections[$key] = $this->repository->settlementPayments($settlement, $table);
        }

        $meterRows = $this->meterSaleService->uniqueRows(
            $this->repository->settlementMeterRows($settlement, $shiftIds)
        );

        return [
            'settlement' => $settlement,
            'shift_ids' => $shiftIds,
            'meter_sales' => $meterRows,
            'meter_sales_total' => $this->meterSaleService->total($meterRows),
            'payments' => $paymentCollections,
            'payment_total' => $this->totalsService->paymentDetailsTotal($paymentCollections),
        ];
    }

    public function listRows(int $businessId, array $filters = []): array
    {
        return $this->repository->listSettlements($businessId, $filters)
            ->map(function ($settlement) use ($businessId) {
                $display = $this->buildDisplayData($businessId, (int) $settlement->id);
                $settlement->pd_payment_total = $display['payment_total'] ?? 0;
                $settlement->pd_meter_sales_total = $display['meter_sales_total'] ?? 0;
                return $settlement;
            })
            ->all();
    }
}
