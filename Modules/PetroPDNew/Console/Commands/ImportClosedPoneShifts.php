<?php

namespace Modules\PetroPDNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\PetroPDNew\Services\Source\PoneSourceImportService;
use Modules\PetroPDNew\Services\Source\PoneSourceReader;

class ImportClosedPoneShifts extends Command
{
    protected $signature = 'petro-pd-new:import-pone {--business=} {--location=} {--limit=100}';
    protected $description = 'Import closed Pumper Dashboard-New shifts into Petro PD-New source snapshots.';

    public function handle(PoneSourceReader $reader, PoneSourceImportService $imports): int
    {
        $businessId = (int) $this->option('business');
        if ($businessId <= 0) {
            $this->error('Use --business=<tenant business id>.');
            return self::FAILURE;
        }

        $locationId = (int) $this->option('location') ?: null;
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $rows = $reader->closedShiftQuery($businessId, $locationId)
            ->whereNull('i.id')
            ->orderBy('s.closed_at')
            ->limit($limit)
            ->get();

        $imported = 0;
        foreach ($rows as $row) {
            $imports->import($businessId, (int) $row->id, 0);
            $imported++;
        }

        $this->info("Imported {$imported} closed Pumper Dashboard-New shift(s).");
        return self::SUCCESS;
    }
}
