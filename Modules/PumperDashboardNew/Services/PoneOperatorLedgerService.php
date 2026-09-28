<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\PumperDashboardNew\Entities\PoneOperatorLedgerEntry;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Entities\PoneShift;

class PoneOperatorLedgerService
{
    public function synchronizeShift(PoneShift $shift): void
    {
        DB::transaction(function () use ($shift): void {
            PoneOperatorLedgerEntry::query()->where('shift_id', $shift->id)->delete();

            $this->write($shift, 'shift_sales', $shift->id, $shift->opened_at ?: now(), $shift->shift_number,
                'Meter sales and other sales for shift ' . $shift->shift_number,
                (float) $shift->expected_total, 0);

            foreach ($shift->payments()->where('status', 'confirmed')->orderBy('transaction_at')->get() as $payment) {
                if (in_array($payment->payment_type, ['shortage', 'excess'], true)) continue;
                $this->write($shift, 'payment', $payment->id, $payment->transaction_at, $payment->payment_number,
                    ucfirst($payment->payment_type) . ' collection ' . $payment->payment_number,
                    0, (float) $payment->amount);
            }

            foreach ($shift->shortageRecoveries()->where('status', 'confirmed')->orderBy('recovery_date')->get() as $recovery) {
                $this->write($shift, 'shortage_recovery', $recovery->id, $recovery->recovery_date, $recovery->recovery_number,
                    'Shortage recovered ' . $recovery->recovery_number,
                    0, (float) $recovery->amount);
            }

            foreach ($shift->excessCommissions()->where('status', 'confirmed')->orderBy('commission_date')->get() as $commission) {
                $this->write($shift, 'excess_commission', $commission->id, $commission->commission_date, $commission->commission_number,
                    'Excess commission ' . $commission->commission_number,
                    0, (float) $commission->commission_amount);
            }
        }, 3);
    }

    public function synchronizeOperator(PonePdOperator $profile): void
    {
        PoneShift::query()->where('business_id', $profile->business_id)
            ->where('operator_profile_id', $profile->id)->orderBy('opened_at')
            ->chunkById(50, function ($shifts): void {
                foreach ($shifts as $shift) $this->synchronizeShift($shift);
            });
    }

    public function entries(PonePdOperator $profile, ?string $from = null, ?string $to = null): Collection
    {
        $rows = PoneOperatorLedgerEntry::query()
            ->where('business_id', $profile->business_id)
            ->where('operator_profile_id', $profile->id)
            ->where('status', 'active')
            ->when($from, fn ($q) => $q->whereDate('entry_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_at', '<=', $to))
            ->orderBy('entry_at')->orderBy('id')->get();

        $balance = 0.0;
        foreach ($rows as $row) {
            $balance += (float) $row->debit - (float) $row->credit;
            $row->setAttribute('running_balance', round($balance, 4));
        }
        return $rows;
    }

    public function balance(PonePdOperator $profile): float
    {
        return round((float) PoneOperatorLedgerEntry::query()
            ->where('business_id', $profile->business_id)
            ->where('operator_profile_id', $profile->id)
            ->where('status', 'active')
            ->selectRaw('COALESCE(SUM(debit-credit),0) as balance')->value('balance'), 4);
    }

    private function write(PoneShift $shift, string $type, int $sourceId, $at, ?string $reference, string $description, float $debit, float $credit): void
    {
        PoneOperatorLedgerEntry::query()->create([
            'entry_key' => implode(':', [$shift->business_id, $shift->operator_profile_id, $type, $sourceId]),
            'business_id' => $shift->business_id,
            'location_id' => $shift->location_id,
            'operator_profile_id' => $shift->operator_profile_id,
            'pd_operator_id' => $shift->pd_operator_id,
            'shift_id' => $shift->id,
            'source_type' => $type,
            'source_id' => $sourceId,
            'entry_at' => $at ?: now(),
            'reference_no' => $reference,
            'description' => $description,
            'debit' => round($debit, 4),
            'credit' => round($credit, 4),
            'status' => 'active',
            'created_by' => (int) auth()->id() ?: $shift->created_by,
        ]);
    }
}
