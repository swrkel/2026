<?php

namespace Modules\PumperDashboardNew\Services\Integration;

use Illuminate\Database\Eloquent\Model;
use Modules\PumperDashboardNew\Entities\PoneDayEntry;
use Modules\PumperDashboardNew\Entities\PoneOtherSale;
use Modules\PumperDashboardNew\Entities\PonePayment;
use Modules\PumperDashboardNew\Entities\PonePumpAssignment;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Entities\PoneUnloadStock;

/**
 * Marks Pumper Dashboard-New records as ready for Petro PD-New.
 *
 * Petro PD-New pulls immutable snapshots from the pone_ tables.  This class
 * intentionally does not write into legacy Petro PD tables or call another
 * module's controllers, models, routes, views, JavaScript, or CSS.
 */
class PonePetroPdNewPublisher
{
    public function syncShift(PoneShift $shift): bool
    {
        return $this->markReady($shift);
    }

    public function syncAssignment(PonePumpAssignment $assignment): bool
    {
        return $this->markReady($assignment);
    }

    public function syncPayment(PonePayment $payment): bool
    {
        return $this->markReady($payment);
    }

    public function syncOtherSale(PoneOtherSale $sale): bool
    {
        return $this->markReady($sale);
    }

    public function syncUnloadStock(PoneUnloadStock $unload): bool
    {
        return $this->markReady($unload);
    }

    public function syncDayEntry(PoneDayEntry $entry): bool
    {
        return $this->markReady($entry);
    }

    public function voidPayment(PonePayment $payment): bool
    {
        return $this->markReady($payment);
    }

    public function voidOtherSale(PoneOtherSale $sale): bool
    {
        return $this->markReady($sale);
    }

    public function deleteOtherSaleLine(Model $line): void
    {
        // Petro PD-New reads the current PONE source graph. No external row is deleted here.
    }

    public function voidUnloadStock(PoneUnloadStock $unload): bool
    {
        return $this->markReady($unload);
    }

    public function deleteUnloadStockLine(Model $line): void
    {
        // Petro PD-New reads the current PONE source graph. No external row is deleted here.
    }

    public function voidDayEntry(PoneDayEntry $entry): bool
    {
        return $this->markReady($entry);
    }

    public function closeShift(PoneShift $shift): bool
    {
        return $this->markReady($shift);
    }

    public function retireSourceLinks(string $sourceType, array $sourceIds): void
    {
        if ($sourceIds === []) {
            return;
        }

        // Compatibility links created by an older package are retired locally.
        \Illuminate\Support\Facades\DB::table('pone_integration_links')
            ->where('source_type', $sourceType)
            ->whereIn('source_id', array_map('intval', $sourceIds))
            ->update([
                'status' => 'retired',
                'last_error' => 'Retired when Pumper Dashboard-New was paired exclusively with Petro PD-New.',
                'updated_at' => now(),
            ]);
    }

    private function markReady(Model $model): bool
    {
        $model->forceFill([
            'integration_status' => 'synced',
            'integration_error' => null,
        ])->saveQuietly();

        return true;
    }
}
