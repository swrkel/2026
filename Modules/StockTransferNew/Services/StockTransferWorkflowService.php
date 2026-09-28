<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\StockTransfer;
use Modules\StockTransferNew\Entities\StockTransferAudit;
use Modules\StockTransferNew\Entities\StockTransferLine;
use Modules\StockTransferNew\Utilities\StockTransferStatus;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class StockTransferWorkflowService
{
    public function __construct(protected StockTransferInventoryService $inventory, protected StockTransferAlertService $alerts) {}

    public function create(array $data, array $lines): StockTransfer
    {
        return DB::transaction(function () use ($data, $lines) {
            $businessId = StockTransferTenant::businessId();
            $transfer = StockTransfer::create(array_merge($data, [
                'business_id' => $businessId,
                'status' => StockTransferStatus::DRAFT,
                'created_by' => StockTransferTenant::userId(),
            ]));
            $this->syncLines($transfer, $lines);
            $this->audit($transfer, 'created', 'Transfer created as draft.');
            return $transfer;
        });
    }

    public function updateDraft(StockTransfer $transfer, array $data, array $lines): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data, $lines) {
            $this->ensureStatus($transfer, [StockTransferStatus::DRAFT, StockTransferStatus::REJECTED], 'Only draft or rejected transfers can be edited.');
            $transfer->update($data);
            $transfer->lines()->delete();
            $this->syncLines($transfer, $lines);
            $this->audit($transfer, 'updated', 'Transfer draft updated.');
            return $transfer->fresh('lines');
        });
    }

    protected function syncLines(StockTransfer $transfer, array $lines): void
    {
        foreach ($lines as $line) {
            if (empty($line['product_id']) || (float)($line['qty_requested'] ?? 0) <= 0) {
                continue;
            }
            $qty = (float)$line['qty_requested'];
            $cost = (float)($line['unit_cost'] ?? 0);
            StockTransferLine::create(array_merge($line, [
                'transfer_id' => $transfer->id,
                'qty_requested' => $qty,
                'line_total' => $qty * $cost,
            ]));
        }
    }

    public function submit(StockTransfer $transfer): void
    {
        $this->ensureStatus($transfer, [StockTransferStatus::DRAFT, StockTransferStatus::REJECTED], 'Only draft/rejected transfers can be submitted.');
        if ($transfer->lines()->count() < 1) {
            throw new \RuntimeException('Please add at least one product before submitting.');
        }
        $transfer->update(['status' => StockTransferStatus::PENDING, 'submitted_at' => now(), 'submitted_by' => StockTransferTenant::userId()]);
        $this->audit($transfer, 'submitted', 'Transfer submitted for approval.');
        $this->alerts->record($transfer, 'submitted', 'Transfer '.$transfer->transfer_no.' submitted for approval.');
    }

    public function approve(StockTransfer $transfer, ?string $note = null): void
    {
        $this->ensureStatus($transfer, [StockTransferStatus::PENDING], 'Only pending transfers can be approved.');
        $transfer->update(['status' => StockTransferStatus::APPROVED, 'approved_at' => now(), 'approved_by' => StockTransferTenant::userId(), 'approval_note' => $note]);
        $this->audit($transfer, 'approved', $note ?: 'Transfer approved.');
        $this->alerts->record($transfer, 'approved', 'Transfer '.$transfer->transfer_no.' approved and ready to dispatch.');
    }

    public function reject(StockTransfer $transfer, ?string $note = null): void
    {
        $this->ensureStatus($transfer, [StockTransferStatus::PENDING], 'Only pending transfers can be rejected.');
        $transfer->update(['status' => StockTransferStatus::REJECTED, 'rejected_at' => now(), 'rejected_by' => StockTransferTenant::userId(), 'rejection_note' => $note]);
        $this->audit($transfer, 'rejected', $note ?: 'Transfer rejected.');
    }

    public function dispatch(StockTransfer $transfer, array $lines = []): void
    {
        $this->ensureStatus($transfer, [StockTransferStatus::APPROVED], 'Only approved transfers can be dispatched.');
        $this->ensureDispatchStockAvailable($transfer);
        $transfer->load('lines');
        foreach ($transfer->lines as $line) {
            $qty = (float)($lines[$line->id] ?? $line->qty_requested);
            $line->update(['qty_dispatched' => $qty]);
        }
        $this->inventory->dispatch($transfer->fresh('lines'), StockTransferTenant::userId());
        $this->audit($transfer, 'dispatched', 'Transfer dispatched and stock moved out.');
        $this->alerts->record($transfer, 'dispatched', 'Transfer '.$transfer->transfer_no.' dispatched and now in transit.');
    }

    public function receive(StockTransfer $transfer, array $lines = []): void
    {
        $this->ensureStatus($transfer, [StockTransferStatus::IN_TRANSIT], 'Only in-transit transfers can be received.');
        $this->inventory->receive($transfer, $lines, StockTransferTenant::userId());
        $this->audit($transfer, 'received', 'Transfer received and stock moved in.');
        $this->alerts->record($transfer, 'received', 'Transfer '.$transfer->transfer_no.' received successfully.');
    }

    public function cancel(StockTransfer $transfer, ?string $reason = null): void
    {
        $this->ensureStatus($transfer, [StockTransferStatus::DRAFT, StockTransferStatus::PENDING, StockTransferStatus::REJECTED], 'This transfer can no longer be cancelled.');
        $transfer->update(['status' => StockTransferStatus::CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => StockTransferTenant::userId(), 'cancel_reason' => $reason]);
        $this->audit($transfer, 'cancelled', $reason ?: 'Transfer cancelled.');
    }


    protected function ensureDispatchStockAvailable(StockTransfer $transfer): void
    {
        $setting = \Modules\StockTransferNew\Entities\StockTransferSetting::where('business_id', $transfer->business_id)->first();
        if (!$setting || !($setting->require_stock_before_dispatch ?? false)) {
            return;
        }
        $transfer->load('lines');
        foreach ($transfer->lines as $line) {
            $available = \Modules\StockTransferNew\Entities\StockTransferBalance::where('business_id', $transfer->business_id)
                ->where('business_location_id', $transfer->from_location_id)
                ->where('store_id', $transfer->from_store_id)
                ->where('product_id', $line->product_id)
                ->where('variation_id', $line->variation_id)
                ->value('qty_on_hand');
            if ((float) $available < (float) $line->qty_requested) {
                throw new \RuntimeException('Insufficient stock for product ID '.$line->product_id.'. Available: '.(float) $available.', Required: '.(float) $line->qty_requested);
            }
        }
    }

    protected function ensureStatus(StockTransfer $transfer, array $allowed, string $message): void
    {
        if (!in_array($transfer->status, $allowed, true)) {
            throw new \RuntimeException($message);
        }
    }

    protected function audit(StockTransfer $transfer, string $action, ?string $note = null): void
    {
        StockTransferAudit::create(['transfer_id' => $transfer->id, 'business_id' => $transfer->business_id, 'action' => $action, 'note' => $note, 'created_by' => StockTransferTenant::userId()]);
    }
}
