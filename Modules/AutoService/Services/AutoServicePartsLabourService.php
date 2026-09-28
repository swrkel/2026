<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceJobLine;
use Modules\AutoService\Entities\AutoServicePartMovement;
use Modules\AutoService\Entities\AutoServiceTimeline;

class AutoServicePartsLabourService
{
    public function saveParts(AutoServiceJob $job, array $rows, bool $syncJobLines = true): AutoServiceJob
    {
        return DB::transaction(function () use ($job, $rows, $syncJobLines) {
            AutoServicePartMovement::where('job_id', $job->id)->delete();

            $partsTotal = 0;
            foreach ($rows as $row) {
                if (empty($row['description']) && empty($row['product_id'])) {
                    continue;
                }

                $qty = max(0, (float)($row['quantity'] ?? 0));
                $unit = max(0, (float)($row['unit_cost'] ?? $row['unit_price'] ?? 0));
                $type = $row['movement_type'] ?? 'reserved';
                $description = $row['description'] ?? $this->productName($row['product_id'] ?? null) ?? 'Auto service part';
                $lineTotal = $qty * $unit;

                AutoServicePartMovement::create([
                    'business_id' => $job->business_id,
                    'location_id' => $job->location_id,
                    'job_id' => $job->id,
                    'product_id' => $row['product_id'] ?? null,
                    'movement_type' => $type,
                    'description' => $description,
                    'quantity' => $qty,
                    'unit_cost' => $unit,
                    'line_total' => $lineTotal,
                    'movement_date' => $row['movement_date'] ?? date('Y-m-d'),
                    'reference_no' => $row['reference_no'] ?? null,
                    'note' => $row['note'] ?? null,
                ]);

                if (in_array($type, ['issued','reserved'], true)) {
                    $partsTotal += $lineTotal;
                } elseif ($type === 'returned') {
                    $partsTotal -= $lineTotal;
                }
            }

            if ($syncJobLines) {
                $this->syncPartsToJobLines($job);
            }

            $this->recalculateJobTotals($job);
            $this->timeline($job, 'parts_labour_parts_updated', 'Parts updated', 'Parts reservation / issue / return list updated.');
            return $job->fresh(['partMovements','lines']);
        });
    }

    public function saveLabour(AutoServiceJob $job, array $rows): AutoServiceJob
    {
        return DB::transaction(function () use ($job, $rows) {
            AutoServiceJobLine::where('job_id', $job->id)->where('line_type', 'labour')->delete();

            foreach ($rows as $row) {
                if (empty($row['description'])) {
                    continue;
                }
                $hours = max(0, (float)($row['hours'] ?? $row['quantity'] ?? 1));
                $rate = max(0, (float)($row['hourly_rate'] ?? $row['unit_price'] ?? 0));
                AutoServiceJobLine::create([
                    'business_id' => $job->business_id,
                    'job_id' => $job->id,
                    'line_type' => 'labour',
                    'product_id' => null,
                    'description' => $row['description'],
                    'quantity' => $hours,
                    'unit_price' => $rate,
                    'line_total' => $hours * $rate,
                ]);
            }

            $this->recalculateJobTotals($job);
            $this->timeline($job, 'parts_labour_labour_updated', 'Labour updated', 'Labour billing lines updated.');
            return $job->fresh(['lines']);
        });
    }

    public function syncPartsToJobLines(AutoServiceJob $job): void
    {
        AutoServiceJobLine::where('job_id', $job->id)->where('line_type', 'part')->delete();

        $movements = AutoServicePartMovement::where('job_id', $job->id)->get();
        foreach ($movements as $movement) {
            if (!in_array($movement->movement_type, ['issued','reserved','returned'], true)) {
                continue;
            }
            $qty = (float)$movement->quantity;
            $unit = (float)$movement->unit_cost;
            if ($movement->movement_type === 'returned') {
                $qty = -1 * abs($qty);
            }
            AutoServiceJobLine::create([
                'business_id' => $job->business_id,
                'job_id' => $job->id,
                'line_type' => 'part',
                'product_id' => $movement->product_id,
                'description' => $movement->description ?: $this->productName($movement->product_id) ?: 'Auto service part',
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $qty * $unit,
            ]);
        }
    }

    public function recalculateJobTotals(AutoServiceJob $job): void
    {
        $subtotal = (float) AutoServiceJobLine::where('job_id', $job->id)->sum('line_total');
        $discount = (float)($job->discount_amount ?? 0);
        $tax = (float)($job->tax_amount ?? 0);
        $paid = Schema::hasTable('auto_service_payments') ? (float) DB::table('auto_service_payments')->where('job_id', $job->id)->sum('amount') : (float)($job->paid_amount ?? 0);
        $total = max(0, $subtotal - $discount + $tax);

        $job->subtotal = $subtotal;
        $job->total_amount = $total;
        $job->paid_amount = $paid;
        $job->balance_amount = max(0, $total - $paid);
        $job->save();
    }

    private function productName($productId): ?string
    {
        if (!$productId || !Schema::hasTable('products')) {
            return null;
        }
        return DB::table('products')->where('id', $productId)->value('name');
    }

    private function timeline(AutoServiceJob $job, string $type, string $title, ?string $description = null): void
    {
        AutoServiceTimeline::create([
            'business_id' => $job->business_id,
            'location_id' => $job->location_id,
            'vehicle_id' => $job->vehicle_id,
            'job_id' => $job->id,
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'event_at' => now(),
        ]);
    }
}
