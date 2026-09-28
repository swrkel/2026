<?php

namespace Modules\MyHealthMembers\Services\DisasterRecovery;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\MyHealthMembers\Entities\MyHealthBackupRecord;
use Modules\MyHealthMembers\Entities\MyHealthRestoreRecord;
use Modules\MyHealthMembers\Entities\MyHealthSystemHealthCheck;
use Modules\MyHealthMembers\Entities\MyHealthRecoveryTest;

class MyHealthDisasterRecoveryService
{
    public function dashboard(): array
    {
        $lastBackup = MyHealthBackupRecord::orderByDesc('id')->first();
        $failedBackups = MyHealthBackupRecord::where('status', 'failed')->count();
        $restorePoints = MyHealthBackupRecord::where('status', 'completed')->count();
        $latestHealth = MyHealthSystemHealthCheck::orderByDesc('checked_at')->limit(10)->get();

        return [
            'last_backup' => $lastBackup,
            'failed_backups' => $failedBackups,
            'restore_points' => $restorePoints,
            'health_checks' => $latestHealth,
            'readiness_score' => $this->readinessScore($lastBackup, $failedBackups, $latestHealth),
            'storage' => $this->storageSummary(),
            'counts' => [
                'backups' => MyHealthBackupRecord::count(),
                'restores' => MyHealthRestoreRecord::count(),
                'health_checks' => MyHealthSystemHealthCheck::count(),
                'recovery_tests' => MyHealthRecoveryTest::count(),
            ],
        ];
    }

    public function backupRecords(array $filters = [])
    {
        $query = MyHealthBackupRecord::query()->orderByDesc('id');
        if (! empty($filters['status'])) { $query->where('status', $filters['status']); }
        if (! empty($filters['backup_type'])) { $query->where('backup_type', $filters['backup_type']); }
        return $query->paginate(25);
    }

    public function restoreRecords(array $filters = [])
    {
        $query = MyHealthRestoreRecord::with('backup')->orderByDesc('id');
        if (! empty($filters['status'])) { $query->where('status', $filters['status']); }
        return $query->paginate(25);
    }

    public function createBackup(array $data, ?int $userId = null): MyHealthBackupRecord
    {
        $backupNo = $this->nextNumber('BKP');
        return MyHealthBackupRecord::create([
            'business_id' => $data['business_id'] ?? $this->businessId(),
            'backup_no' => $backupNo,
            'backup_type' => $data['backup_type'] ?? 'manual',
            'backup_scope' => $data['backup_scope'] ?? 'database_and_files',
            'status' => 'queued',
            'storage_disk' => $data['storage_disk'] ?? 'local',
            'storage_path' => 'myhealth/backups/' . $backupNo,
            'started_at' => now(),
            'created_by' => $userId,
            'notes' => $data['notes'] ?? null,
            'metadata' => ['requested_from' => 'myhealth_disaster_recovery'],
        ]);
    }

    public function createRestore(array $data, ?int $userId = null): MyHealthRestoreRecord
    {
        return MyHealthRestoreRecord::create([
            'business_id' => $data['business_id'] ?? $this->businessId(),
            'restore_no' => $this->nextNumber('RST'),
            'backup_record_id' => $data['backup_record_id'] ?? null,
            'restore_scope' => $data['restore_scope'] ?? 'preview',
            'status' => 'requested',
            'requested_by' => $userId,
            'notes' => $data['notes'] ?? null,
            'metadata' => ['restore_mode' => 'wizard'],
        ]);
    }

    public function runHealthChecks(): array
    {
        $checks = [
            ['database', 'Database Connectivity', $this->databaseOk(), 'Database connection check completed.'],
            ['migrations', 'My Health Tables', $this->tablesOk(), 'Key My Health tables availability check completed.'],
            ['storage', 'Storage Writable', $this->storageWritable(), 'Storage write/read check completed.'],
            ['backup_age', 'Backup Freshness', $this->backupFresh(), 'Latest backup freshness check completed.'],
        ];

        $records = [];
        foreach ($checks as [$key, $name, $ok, $message]) {
            $records[] = MyHealthSystemHealthCheck::create([
                'business_id' => $this->businessId(),
                'check_key' => $key,
                'check_name' => $name,
                'status' => $ok ? 'passed' : 'failed',
                'severity' => $ok ? 'normal' : 'critical',
                'checked_at' => now(),
                'message' => $message,
                'details' => ['result' => $ok],
            ]);
        }
        return $records;
    }

    public function recoveryTests()
    {
        return MyHealthRecoveryTest::orderByDesc('id')->paginate(25);
    }

    public function reports(array $filters = []): array
    {
        return [
            'backup_history' => MyHealthBackupRecord::orderByDesc('id')->limit(50)->get(),
            'restore_history' => MyHealthRestoreRecord::orderByDesc('id')->limit(50)->get(),
            'system_health' => MyHealthSystemHealthCheck::orderByDesc('checked_at')->limit(50)->get(),
            'recovery_tests' => MyHealthRecoveryTest::orderByDesc('id')->limit(50)->get(),
        ];
    }

    protected function readinessScore($lastBackup, int $failedBackups, $healthChecks): int
    {
        $score = 100;
        if (! $lastBackup) { $score -= 35; }
        elseif ($lastBackup->created_at && $lastBackup->created_at->lt(now()->subDays(2))) { $score -= 20; }
        if ($failedBackups > 0) { $score -= min(25, $failedBackups * 5); }
        foreach ($healthChecks as $check) {
            if ($check->status === 'failed') { $score -= $check->severity === 'critical' ? 15 : 5; }
        }
        return max(0, min(100, $score));
    }

    protected function storageSummary(): array
    {
        return [
            'disk' => config('filesystems.default', 'local'),
            'backup_path' => 'myhealth/backups',
            'status' => $this->storageWritable() ? 'Writable' : 'Not writable',
        ];
    }

    protected function databaseOk(): bool
    {
        try { DB::connection()->getPdo(); return true; } catch (\Throwable $e) { return false; }
    }

    protected function tablesOk(): bool
    {
        return Schema::hasTable('myhealth_members') && Schema::hasTable('myhealth_backup_records');
    }

    protected function storageWritable(): bool
    {
        try {
            $path = 'myhealth/healthcheck_' . date('YmdHis') . '.txt';
            Storage::put($path, 'ok');
            $ok = Storage::exists($path);
            Storage::delete($path);
            return $ok;
        } catch (\Throwable $e) { return false; }
    }

    protected function backupFresh(): bool
    {
        $backup = MyHealthBackupRecord::where('status', 'completed')->orderByDesc('completed_at')->first();
        return $backup && $backup->completed_at && $backup->completed_at->gte(now()->subDays(2));
    }

    protected function businessId(): ?int
    {
        return session('business.id') ?? session('business_id') ?? null;
    }

    protected function nextNumber(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
