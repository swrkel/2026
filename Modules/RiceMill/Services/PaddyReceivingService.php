<?php
namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\{PaddyReceipt,PaddyLot,WeighbridgeEntry,PaddyVariety};

class PaddyReceivingService
{
    public function __construct(private NumberSeriesService $numbers, private PaddyStockService $stock, private FinanceAccountPostingService $financeAccounts) {}

    public function receive(int $businessId, int $userId, array $data): PaddyReceipt
    {
        return DB::transaction(function () use ($businessId, $userId, $data) {
            $gross = (float) $data['gross_weight'];
            $tare = (float) $data['tare_weight'];
            $net = $gross - $tare;
            if ($net <= 0) {
                throw new \InvalidArgumentException('Net weight must be greater than zero.');
            }

            $variety = PaddyVariety::forBusiness($businessId)
                ->where('active', 1)
                ->select([
                    'id','business_id','code','default_moisture_percent',
                    'foreign_matter_limit_percent','quality_grade','lot_opening_number'
                ])
                ->findOrFail((int) $data['paddy_variety_id']);

            if (trim((string) $variety->code) === '') {
                throw new \RuntimeException('Please configure a Paddy Variety Code in Rice Mill Settings before receiving this variety.');
            }

            // PD is the approved Rice Mill prefix. The old Settings lookup was
            // redundant because the value was overwritten to PD immediately.
            $documentPrefix = 'PD';

            if (!array_key_exists('moisture_percent', $data) || $data['moisture_percent'] === null || $data['moisture_percent'] === '') {
                $data['moisture_percent'] = $variety->default_moisture_percent;
            }
            if ((!array_key_exists('foreign_matter_percent', $data) || $data['foreign_matter_percent'] === null || $data['foreign_matter_percent'] === '')
                && $variety->foreign_matter_limit_percent !== null) {
                $data['foreign_matter_percent'] = $variety->foreign_matter_limit_percent;
            }
            if ((!array_key_exists('foreign_matter_limit_percent', $data) || $data['foreign_matter_limit_percent'] === null || $data['foreign_matter_limit_percent'] === '')
                && $variety->foreign_matter_limit_percent !== null) {
                $data['foreign_matter_limit_percent'] = $variety->foreign_matter_limit_percent;
            }
            if ((!array_key_exists('quality_grade', $data) || trim((string) $data['quality_grade']) === '') && $variety->quality_grade) {
                $data['quality_grade'] = $variety->quality_grade;
            }

            $opening = max(1, (int) ($variety->lot_opening_number ?: 1));
            $lotPrefix = $this->numbers->varietyLotPrefix($documentPrefix, (string) $variety->code);

            // Reserve the three numbers together. This keeps concurrency safety
            // while avoiding three separate number-series read/update cycles.
            $numbers = $this->numbers->nextMany($businessId, [
                'weighbridge' => [
                    'type' => 'weighbridge',
                    'prefix' => $documentPrefix . '-WB-',
                    'opening' => 1,
                ],
                'receipt' => [
                    'type' => 'paddy_receipt',
                    'prefix' => $documentPrefix . '-RCV-',
                    'opening' => 1,
                ],
                'lot' => [
                    'type' => $this->numbers->varietyLotType((int) $variety->id),
                    'prefix' => $lotPrefix,
                    'opening' => $opening,
                ],
            ]);

            $entry = WeighbridgeEntry::create([
                'business_id' => $businessId,
                'location_id' => $data['location_id'] ?? null,
                'entry_no' => $numbers['weighbridge'],
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'gross_weight' => $gross,
                'tare_weight' => $tare,
                'net_weight' => $net,
                'weighed_at' => $data['received_at'] ?? now(),
                'created_by' => $userId,
            ]);

            $receipt = PaddyReceipt::create(array_merge($data, [
                'business_id' => $businessId,
                'weighbridge_entry_id' => $entry->id,
                'receipt_no' => $numbers['receipt'],
                'net_weight' => $net,
                'status' => 'received',
                'received_at' => $data['received_at'] ?? now(),
                'created_by' => $userId,
            ]));

            $lot = PaddyLot::create([
                'business_id' => $businessId,
                'location_id' => $receipt->location_id,
                'store_id' => $receipt->store_id,
                'lot_no' => $numbers['lot'],
                'receipt_id' => $receipt->id,
                'paddy_variety_id' => $receipt->paddy_variety_id,
                'supplier_id' => $receipt->supplier_id,
                'original_qty' => $net,
                'balance_qty' => 0,
                'moisture_percent' => $receipt->moisture_percent,
                'quality_grade' => $receipt->quality_grade,
                'received_date' => substr((string) $receipt->received_at, 0, 10),
                'status' => 'available',
                'created_by' => $userId,
            ]);

            $this->stock->move($businessId, $lot->id, 'receipt', $net, [
                'location_id' => $receipt->location_id,
                'store_id' => $receipt->store_id,
                'reference_type' => 'paddy_receipt',
                'reference_id' => $receipt->id,
                'created_by' => $userId,
            ]);

            $receipt->update(['paddy_lot_id' => $lot->id]);
            $entry->update(['receipt_id' => $receipt->id]);
            $receipt = $receipt->fresh();
            $this->financeAccounts->syncPaddyReceipt($businessId,$userId,$receipt);
            return $receipt;
        });
    }
}
