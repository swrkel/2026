<?php

namespace Modules\PetroPDNew\Services\Source;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewSourceImport;
use Modules\PetroPDNew\Entities\PdnewSourceSnapshot;
use Modules\PetroPDNew\Services\PdnewAuditService;
use Modules\PetroPDNew\Services\PdnewOperatorMappingService;

class PoneSourceImportService
{
    public function __construct(
        private PoneSourceReader $reader,
        private PdnewOperatorMappingService $operators,
        private PdnewAuditService $audit
    ) {}

    public function import(int $businessId, int $shiftId, int $userId): PdnewSourceImport
    {
        return DB::transaction(function () use ($businessId, $shiftId, $userId): PdnewSourceImport {
            // The PONE shift is the stable concurrency key even when the
            // pdnew_source_imports row has not been created yet. Locking it
            // serializes first-time imports and concurrent settlement requests.
            $lockedShift = DB::table('pone_shifts')
                ->where('business_id', $businessId)
                ->where('id', $shiftId)
                ->lockForUpdate()
                ->first();

            if (! $lockedShift) {
                throw new \RuntimeException(
                    'The selected Pumper Dashboard-New shift is outside the active business.'
                );
            }

            $snapshot = $this->reader->snapshot($businessId, $shiftId);
            $shift = (array) $snapshot['shift'];
            $hash = (string) $snapshot['source_hash'];

            $source = PdnewSourceImport::query()->lockForUpdate()->firstOrNew([
                'business_id' => $businessId,
                'pone_shift_id' => $shiftId,
            ]);

            $oldHash = (string) $source->source_hash;
            $version = $source->exists
                ? max(1, (int) $source->snapshot_version + ($oldHash !== $hash ? 1 : 0))
                : 1;

            $source->fill([
                'location_id' => (int) ($shift['location_id'] ?? 0) ?: null,
                'pone_shift_uuid' => $shift['uuid'] ?? null,
                'pone_shift_number' => (string) $shift['shift_number'],
                'pone_operator_profile_id' => (int) $shift['operator_profile_id'],
                'pone_pd_operator_id' => (int) $shift['pd_operator_id'],
                'source_status' => (string) $shift['status'],
                'import_status' => $this->matchingStatus($source),
                'source_hash' => $hash,
                'current_hash' => $hash,
                'snapshot_version' => $version,
                'source_closed_at' => $shift['closed_at'] ?? null,
                'source_totals' => [
                    'meter_sales_total' => $shift['meter_sales_total'] ?? 0,
                    'other_sales_total' => $shift['other_sales_total'] ?? 0,
                    'payments_total' => $shift['payments_total'] ?? 0,
                    'declared_total' => $shift['declared_total'] ?? $shift['payments_total'] ?? 0,
                    'expected_total' => $shift['expected_total'] ?? 0,
                    'shortage_amount' => $shift['shortage_amount'] ?? 0,
                    'excess_amount' => $shift['excess_amount'] ?? 0,
                ],
                'imported_at' => now(),
                'verified_at' => now(),
                'last_error' => null,
                'imported_by' => $userId ?: null,
            ])->save();

            $snapshotModel = PdnewSourceSnapshot::query()->firstOrCreate(
                ['source_import_id' => $source->id, 'version' => $version],
                [
                    'business_id' => $businessId,
                    'pone_shift_id' => $shiftId,
                    'source_hash' => $hash,
                    'snapshot' => $snapshot,
                    'captured_at' => now(),
                    'captured_by' => $userId ?: null,
                ]
            );

            if ($snapshotModel->source_hash !== $hash) {
                $snapshotModel->update([
                    'source_hash' => $hash,
                    'snapshot' => $snapshot,
                    'captured_at' => now(),
                    'captured_by' => $userId ?: null,
                ]);
            }

            $this->operators->syncFromSnapshot($businessId, $snapshot);
            $this->audit->log('source.imported', 'pdnew_source_import', $source->id, null, [
                'business_id' => $businessId,
                'location_id' => (int) ($shift['location_id'] ?? 0) ?: null,
                'pone_shift_id' => $shiftId,
                'source_hash' => $hash,
                'version' => $version,
            ], $businessId, (int) ($shift['location_id'] ?? 0) ?: null, $userId ?: null);

            return $source->fresh();
        }, 3);
    }

    public function currentSnapshot(PdnewSourceImport $source): array
    {
        $snapshot = $source->snapshots()
            ->where('version', $source->snapshot_version)
            ->firstOrFail();

        return (array) $snapshot->snapshot;
    }

    public function verify(PdnewSourceImport $source): array
    {
        $snapshot = $this->reader->snapshot((int) $source->business_id, (int) $source->pone_shift_id);
        $currentHash = (string) $snapshot['source_hash'];
        $matches = hash_equals((string) $source->source_hash, $currentHash);

        $source->update([
            'current_hash' => $currentHash,
            'verified_at' => now(),
            'import_status' => $matches ? $this->matchingStatus($source) : 'changed',
            'last_error' => $matches ? null : 'The Pumper Dashboard-New source changed after import.',
        ]);

        return ['matches' => $matches, 'current_hash' => $currentHash, 'snapshot' => $snapshot];
    }

    private function matchingStatus(PdnewSourceImport $source): string
    {
        if (in_array((string) $source->import_status, ['finalized', 'cancelled'], true)) {
            return (string) $source->import_status;
        }

        return $source->settlement_id ? 'settled' : 'imported';
    }
}
