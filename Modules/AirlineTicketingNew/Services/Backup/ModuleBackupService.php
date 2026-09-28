<?php
namespace Modules\AirlineTicketingNew\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AirlineTicketingNew\Entities\BackupRecord;
use RuntimeException;

class ModuleBackupService
{
    public function create(int $businessId, string $disk = 'local'): BackupRecord
    {
        $record = BackupRecord::query()->create([
            'business_id' => $businessId,
            'backup_type' => 'module_data',
            'storage_disk' => $disk,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $tables = DB::select("SHOW TABLES LIKE 'atn\_%'");
            $payload = [];

            foreach ($tables as $tableRow) {
                $table = array_values((array) $tableRow)[0];
                $payload[$table] = DB::table($table)
                    ->where('business_id', $businessId)
                    ->get()
                    ->map(fn ($row) => (array) $row)
                    ->all();
            }

            $path = 'airline-ticketing-new/backups/' . $businessId . '/atn-' . now()->format('Ymd-His') . '.json';
            Storage::disk($disk)->put($path, json_encode($payload, JSON_PRETTY_PRINT));

            $record->update([
                'file_path' => $path,
                'file_size' => Storage::disk($disk)->size($path),
                'status' => 'completed',
                'completed_at' => now(),
                'metadata_json' => ['tables' => array_keys($payload)],
            ]);
        } catch (\Throwable $e) {
            $record->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw new RuntimeException('Airline Ticketing backup failed.', 0, $e);
        }

        return $record->refresh();
    }
}
