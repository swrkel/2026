<?php

namespace Modules\Poultry\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\Treatment;
use Modules\Poultry\Entities\VaccinationRecord;
use Modules\Poultry\Entities\VaccinationSchedule;

class HealthService
{
    protected $stock;
    protected $ledger;

    public function __construct(StockGateway $stock, LedgerGateway $ledger)
    {
        $this->stock  = $stock;
        $this->ledger = $ledger;
    }

    /**
     * The vaccination plan for a batch: every applicable schedule row with its
     * due date, and whether it has been administered. Drives the due list and
     * the overdue alert on the dashboard.
     */
    public function schedule(Batch $batch)
    {
        $rows = VaccinationSchedule::query()->forBusiness($batch->business_id)
            ->forBatch($batch)
            ->get();

        $done = VaccinationRecord::where('batch_id', $batch->id)
            ->pluck('administered_on', 'schedule_id');

        $placement = Carbon::parse($batch->placement_date);
        $today     = Carbon::today();

        return $rows->map(function ($row) use ($done, $placement, $today, $batch) {
            $dueDate = $placement->copy()->addDays($row->age_days);
            $givenOn = $done[$row->id] ?? null;

            return [
                'schedule'    => $row,
                'due_date'    => $dueDate->toDateString(),
                'age_days'    => $row->age_days,
                'given_on'    => $givenOn,
                'is_done'     => ! empty($givenOn),
                'is_overdue'  => empty($givenOn) && $dueDate->lt($today) && $batch->is_open,
                'days_until'  => $today->diffInDays($dueDate, false),
            ];
        });
    }

    public function recordVaccination(Batch $batch, array $data)
    {
        return DB::transaction(function () use ($batch, $data) {
            $record = VaccinationRecord::create([
                'business_id'      => $batch->business_id,
                'batch_id'         => $batch->id,
                'schedule_id'      => $data['schedule_id'] ?? null,
                'name'             => $data['name'],
                'product_id'       => $data['product_id'] ?? null,
                'variation_id'     => $data['variation_id'] ?? null,
                'administered_on'  => $data['administered_on'],
                'age_days'         => $batch->ageInDays($data['administered_on']),
                'birds_covered'    => $data['birds_covered'] ?? $batch->current_qty,
                'dose'             => $data['dose'] ?? null,
                'route'            => $data['route'] ?? null,
                'vaccine_batch_no' => $data['vaccine_batch_no'] ?? null,
                'administered_by'  => $data['administered_by'] ?? null,
                'qty_used'         => $data['qty_used'] ?? 0,
                'total_cost'       => $data['total_cost'] ?? 0,
                'notes'            => $data['notes'] ?? null,
            ]);

            $this->issueStockIfAny($batch, $record, $data, 'Vaccination - ');

            if ($record->total_cost > 0) {
                $this->ledger->recordCost(
                    $batch->id,
                    'vaccination',
                    $record->total_cost,
                    $data['administered_on'],
                    'vaccination_record',
                    $record->id,
                    $record->name.' - '.$batch->batch_code
                );
            }

            return $record;
        });
    }

    /**
     * Record a medication course. The withdrawal end date is computed from the
     * LAST day of treatment, not the first - setting it from the start date is
     * a common and consequential error.
     */
    public function recordTreatment(Batch $batch, array $data)
    {
        return DB::transaction(function () use ($batch, $data) {
            $treatment = new Treatment([
                'business_id'     => $batch->business_id,
                'batch_id'        => $batch->id,
                'name'            => $data['name'],
                'product_id'      => $data['product_id'] ?? null,
                'variation_id'    => $data['variation_id'] ?? null,
                'diagnosis'       => $data['diagnosis'] ?? null,
                'started_on'      => $data['started_on'],
                'ended_on'        => $data['ended_on'] ?? null,
                'dosage'          => $data['dosage'] ?? null,
                'route'           => $data['route'] ?? null,
                'withdrawal_days' => $data['withdrawal_days'] ?? 0,
                'vet_name'        => $data['vet_name'] ?? null,
                'qty_used'        => $data['qty_used'] ?? 0,
                'total_cost'      => $data['total_cost'] ?? 0,
                'notes'           => $data['notes'] ?? null,
            ]);

            $treatment->batch_id         = $batch->id;
            $treatment->withdrawal_until = $treatment->calculateWithdrawalUntil();
            $treatment->save();

            $this->issueStockIfAny($batch, $treatment, $data, 'Medication - ');

            if ($treatment->total_cost > 0) {
                $this->ledger->recordCost(
                    $batch->id,
                    'medication',
                    $treatment->total_cost,
                    $data['started_on'],
                    'treatment',
                    $treatment->id,
                    $treatment->name.' - '.$batch->batch_code
                );
            }

            return $treatment;
        });
    }

    /** Shared code path for consuming a stocked vaccine or drug. */
    protected function issueStockIfAny(Batch $batch, $record, array $data, $notePrefix)
    {
        if (empty($data['variation_id']) || empty($data['location_id']) || empty($data['qty_used'])) {
            return;
        }

        $transactionId = $this->stock->issue([
            'business_id'    => $batch->business_id,
            'product_id'     => $data['product_id'],
            'variation_id'   => $data['variation_id'],
            'location_id'    => $data['location_id'],
            'qty'            => $data['qty_used'],
            'total_cost'     => $data['total_cost'] ?? 0,
            'date'           => $data['administered_on'] ?? $data['started_on'],
            'notes'          => $notePrefix.$batch->batch_code,
            'allow_overdraw' => true,
        ]);

        if ($transactionId) {
            $record->stock_transaction_id = $transactionId;
            $record->save();
        }
    }
}
