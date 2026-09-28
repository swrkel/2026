<?php

namespace App\Http\Controllers;

use App\Business;
use App\SmsLog;
use Illuminate\Http\Request;
use Storage;
use Log;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Entities\Package;
use App\Utils\Util;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Sarfraznawaz2005\BackupManager\Facades\BackupManager;
class BackUpController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('backup')) {
            abort(403, 'Unauthorized action.');
        }
        
        
        $business_id = request()->session()->get('user.business_id');
        $subscription = Subscription::active_subscription($business_id);
        $cron_job_command = $this->commonUtil->getCronJobCommand();
        $backups = $this->getBackupsDirectly();
        return view("backup.index")
            ->with(compact('backups', 'cron_job_command'));
    }
    
    public function cronBackup(){
        if ($this->hasReachedBackupRetentionLimit()) {
            FacadesLog::warning('Backup skipped because backup retention count has been reached.');
            return $this->backupRetentionLimitOutput();
        }

        $result = BackupManager::cron_backupDatabase();
    }

    /**
     * Create a resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // BCK-CREATE-AJAX-034: GET /backup/create should never create a backup.
        // Older code used the resource "create" route as the action itself, so browser refresh/back
        // could create duplicate backups. Keep GET safe and redirect back to the backup list.
        if (!$request->isMethod('post')) {
            return redirect('backup');
        }

        return $this->createBackupAjax($request);
    }

    public function createBackupAjax(Request $request)
    {
        set_time_limit(0);

        if (!auth()->user()->can('backup')) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->commonUtil->notAllowedInDemo();
        if (!empty($notAllowed)) {
            return $request->ajax()
                ? response()->json(['success' => 0, 'msg' => is_array($notAllowed) ? ($notAllowed['msg'] ?? 'Not allowed.') : 'Not allowed.'], 403)
                : $notAllowed;
        }

        if ($this->hasReachedBackupRetentionLimit()) {
            $output = $this->backupRetentionLimitOutput();
            return $request->ajax()
                ? response()->json($output, 422)
                : redirect('backup')->with('status', $output);
        }

        $lockHandle = $this->acquireBackupCreateLock();
        if ($lockHandle === false) {
            $output = [
                'success' => 0,
                'msg' => 'A backup is already being created. Please wait until it finishes.',
            ];

            return $request->ajax()
                ? response()->json($output, 429)
                : redirect('backup')->with('status', $output);
        }

        try {
            // BCK-CREATE-DIRECT-037: create the database backup directly into the backup folder.
            // Do not use BackupManager here because it can create file backups (f_*.tar), touch old
            // backup mtimes, or be triggered again by refresh on this installation.
            $newBackup = $this->createDatabaseBackupFileDirectly();

            FacadesLog::info('backup direct database create success', [
                'file' => $newBackup['name'] ?? null,
                'size' => $newBackup['size_raw'] ?? null,
            ]);

            $output = [
                'success' => 1,
                'msg' => 'Database backup created successfully.',
                'backup' => $newBackup,
                'row_html' => $newBackup ? $this->backupRowHtml($newBackup) : null,
            ];

            return $request->ajax()
                ? response()->json($output)
                : redirect('backup')->with('status', $output);
        } catch (\Exception $e) {
            FacadesLog::error('Backup ajax create exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];

            return $request->ajax()
                ? response()->json($output, 500)
                : redirect('backup')->with('status', $output);
        } finally {
            $this->releaseBackupCreateLock($lockHandle);
        }
    }


    /**
     * Create a real database-only .gz backup without calling BackupManager.
     * This fixes the repeated f_*.tar backups and the create failure after the stable-time patch.
     */
    private function createDatabaseBackupFileDirectly(): array
    {
        $disk = config('backupmanager.backups.disk');
        $backupPath = trim(config('backupmanager.backups.backup_path'), DIRECTORY_SEPARATOR);

        if ($backupPath !== '') {
            FacadesStorage::disk($disk)->makeDirectory($backupPath);
        }

        $filename = 'd_' . Carbon::now(config('app.timezone'))->format('M-d-Y-H-i-s') . '.gz';
        $relativePath = $backupPath === '' ? $filename : $backupPath . DIRECTORY_SEPARATOR . $filename;

        $tempDir = storage_path('app/backup-temp');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $tempFile = $tempDir . DIRECTORY_SEPARATOR . $filename;
        $gz = @gzopen($tempFile, 'wb9');

        if (!$gz) {
            throw new \Exception('Unable to create backup file. Please check storage/app/backup-temp permission.');
        }

        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $database = $connection->getDatabaseName();

            gzwrite($gz, "-- Database backup\n");
            gzwrite($gz, "-- Database: `" . str_replace('`', '``', $database) . "`\n");
            gzwrite($gz, "-- Created: " . Carbon::now(config('app.timezone'))->format('Y-m-d H:i:s') . "\n\n");
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tableRows = $connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']);
            $tableKey = 'Tables_in_' . $database;

            foreach ($tableRows as $row) {
                $rowArray = (array) $row;
                $table = $rowArray[$tableKey] ?? reset($rowArray);

                if (empty($table)) {
                    continue;
                }

                $quotedTable = '`' . str_replace('`', '``', $table) . '`';

                $createRows = $connection->select('SHOW CREATE TABLE ' . $quotedTable);
                $createArray = (array) ($createRows[0] ?? []);
                $createSql = $createArray['Create Table'] ?? array_values($createArray)[1] ?? null;

                if (!$createSql) {
                    continue;
                }

                gzwrite($gz, "DROP TABLE IF EXISTS {$quotedTable};\n");
                gzwrite($gz, $createSql . ";\n\n");

                $columns = $connection->select('SHOW COLUMNS FROM ' . $quotedTable);
                $columnNames = array_map(function ($column) {
                    return $column->Field;
                }, $columns);

                $quotedColumns = '`' . implode('`,`', array_map(function ($column) {
                    return str_replace('`', '``', $column);
                }, $columnNames)) . '`';

                $offset = 0;
                $limit = 500;

                do {
                    $records = $connection->select('SELECT * FROM ' . $quotedTable . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);

                    if (empty($records)) {
                        break;
                    }

                    foreach ($records as $record) {
                        $values = [];
                        $recordArray = (array) $record;

                        foreach ($columnNames as $columnName) {
                            $value = $recordArray[$columnName] ?? null;
                            $values[] = is_null($value) ? 'NULL' : $pdo->quote($value);
                        }

                        gzwrite($gz, 'INSERT INTO ' . $quotedTable . ' (' . $quotedColumns . ') VALUES (' . implode(',', $values) . ");\n");
                    }

                    $offset += $limit;
                } while (count($records) === $limit);

                gzwrite($gz, "\n");
            }

            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            gzclose($gz);
        }

        FacadesStorage::disk($disk)->put($relativePath, file_get_contents($tempFile));
        @unlink($tempFile);

        if (!FacadesStorage::disk($disk)->exists($relativePath)) {
            throw new \Exception('Backup file was not saved to the backup folder.');
        }

        $size = (int) FacadesStorage::disk($disk)->size($relativePath);
        if ($size <= 0) {
            FacadesStorage::disk($disk)->delete($relativePath);
            throw new \Exception('Backup file was created as 0B and was removed.');
        }

        $timestamp = $this->getBackupTimestampFromFilename($filename) ?: time();

        return [
            'name' => $filename,
            'size_raw' => $size,
            'date_raw' => $timestamp,
            'date' => $this->formatBackupDate($timestamp),
            'age' => $this->formatBackupAge($timestamp),
        ];
    }

    private function acquireBackupCreateLock()
    {
        $lockDir = storage_path('framework/cache');
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0775, true);
        }

        $lockFile = $lockDir . DIRECTORY_SEPARATOR . 'backup_create.lock';

        // Remove stale lock older than 15 minutes.
        if (is_file($lockFile) && (time() - filemtime($lockFile)) > 900) {
            @unlink($lockFile);
        }

        $handle = @fopen($lockFile, 'x');
        if ($handle === false) {
            return false;
        }

        fwrite($handle, (string) time());
        return $handle;
    }

    private function releaseBackupCreateLock($handle): void
    {
        if (is_resource($handle)) {
            @fclose($handle);
        }

        @unlink(storage_path('framework/cache/backup_create.lock'));
    }

    private function findNewestCreatedBackup($backups, array $before): ?array
    {
        $new = collect($backups)
            ->filter(function ($backup) use ($before) {
                return isset($backup['name']) && !in_array($backup['name'], $before, true);
            })
            ->sortByDesc(function ($backup) {
                return $backup['date_raw'] ?? $backup['date'] ?? $backup['name'];
            })
            ->first();

        if (!$new) {
            $new = collect($backups)
                ->sortByDesc(function ($backup) {
                    return $backup['date_raw'] ?? $backup['date'] ?? $backup['name'];
                })
                ->first();
        }

        return $new ?: null;
    }

    private function backupRowHtml(array $backup): string
    {
        $name = e($backup['name']);
        $size = e(humanFilesize($backup['size_raw'] ?? 0));
        $date = e($backup['date'] ?? '');
        $downloadUrl = action('BackUpController@download', [$backup['name']]);
        $deleteUrl = action('BackUpController@delete', [$backup['name']]);
        $restoreUrl = url('backup/restore/' . $backup['name']);
        $restoreButton = '';

        if (auth()->user()->can('backup.restore')) {
            $restoreButton = '<a class="btn btn-xs btn-primary link_confirmation" data-button-type="restore" href="' . e($restoreUrl) . '"><i class="fa fa-undo"></i> Restore</a>';
        }

        return '<tr data-backup-file="' . $name . '">' .
            '<td>' . $name . '</td>' .
            '<td>' . $size . '</td>' .
            '<td>' . $date . '</td>' .
            '<td>' . e($backup['age'] ?? '-') . '</td>' .
            '<td>' .
                '<a class="btn btn-xs btn-success" href="' . e($downloadUrl) . '"><i class="fa fa-cloud-download"></i> ' . e(__('lang_v1.download')) . '</a> ' .
                '<a class="btn btn-xs btn-danger backup-delete-btn" data-file="' . $name . '" data-url="' . e($deleteUrl) . '" href="' . e($deleteUrl) . '"><i class="fa fa-trash-o"></i> ' . e(__('messages.delete')) . '</a> ' .
                $restoreButton .
            '</td>' .
        '</tr>';
    }

    /**
     * BCK-STABLE-TIME-036
     * Prefer the timestamp embedded in the backup filename instead of filesystem mtime.
     * Some storage/scanning operations can touch archive mtimes during refresh/download/delete,
     * which made the displayed date/time change even though the backup file was old.
     */
    private function getBackupTimestampFromFilename(string $filename): ?int
    {
        $name = basename($filename);

        // Current DB backups: d_Jun-27-2026-20-10-33.gz
        if (preg_match('/^[df]_([A-Za-z]{3})-(\d{2})-(\d{4})-(\d{2})-(\d{2})-(\d{2})\.(gz|tar|zip)$/i', $name, $m)) {
            try {
                return Carbon::createFromFormat('M-d-Y-H-i-s', $m[1].'-'.$m[2].'-'.$m[3].'-'.$m[4].'-'.$m[5].'-'.$m[6], config('app.timezone'))->timestamp;
            } catch (\Exception $e) {
                return null;
            }
        }

        // Older backups: f_06-27-26-03-34-46.tar
        if (preg_match('/^[df]_(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})\.(gz|tar|zip)$/i', $name, $m)) {
            try {
                return Carbon::createFromFormat('m-d-y-H-i-s', $m[1].'-'.$m[2].'-'.$m[3].'-'.$m[4].'-'.$m[5].'-'.$m[6], config('app.timezone'))->timestamp;
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    private function formatBackupDate(int $timestamp): string
    {
        try {
            return Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('M d Y');
        } catch (\Exception $e) {
            return date('M d Y', $timestamp);
        }
    }

    private function formatBackupAge(int $timestamp): string
    {
        // The existing UI uses this column as backup time. Keep it fixed from filename timestamp.
        try {
            return Carbon::createFromTimestamp($timestamp, config('app.timezone'))->format('h:i A');
        } catch (\Exception $e) {
            return date('h:i A', $timestamp);
        }
    }

    /**
     * BCK-REFRESH-STABLE-035
     * Read backup list directly from the configured backup folder.
     * This avoids BackupManager rescans/touches on page refresh and keeps the shown
     * created date/time tied to the real file modified time only.
     */
    private function getBackupsDirectly(): array
    {
        $disk = config('backupmanager.backups.disk');
        $backupPath = trim(config('backupmanager.backups.backup_path'), DIRECTORY_SEPARATOR);

        try {
            $files = FacadesStorage::disk($disk)->files($backupPath);
        } catch (\Exception $e) {
            FacadesLog::warning('Direct backup list failed', ['error' => $e->getMessage()]);
            return [];
        }

        $backups = [];

        foreach ($files as $relativePath) {
            $name = basename($relativePath);

            if ($name === '' || !preg_match('/\.(gz|tar|zip)$/i', $name)) {
                continue;
            }

            try {
                $size = (int) FacadesStorage::disk($disk)->size($relativePath);
            } catch (\Exception $e) {
                $size = 0;
            }

            $timestampFromName = $this->getBackupTimestampFromFilename($name);

            if ($timestampFromName !== null) {
                $modified = $timestampFromName;
            } else {
                try {
                    $modified = (int) FacadesStorage::disk($disk)->lastModified($relativePath);
                } catch (\Exception $e) {
                    $modified = time();
                }
            }

            $backups[] = [
                'name' => $name,
                'size_raw' => $size,
                'date_raw' => $modified,
                'date' => $this->formatBackupDate($modified),
                'age' => $this->formatBackupAge($modified),
            ];
        }

        usort($backups, function ($a, $b) {
            return ($b['date_raw'] ?? 0) <=> ($a['date_raw'] ?? 0);
        });

        return $backups;
    }

    protected function hasReachedBackupRetentionLimit(): bool
    {
        $backupLimit = max(0, (int) env('BACKUPS_RETAIN_COUNT', 0));

        if ($backupLimit === 0) {
            return false;
        }

        return count($this->getBackupsDirectly()) >= $backupLimit;
    }

    protected function backupRetentionLimitOutput(): array
    {
        return [
            'success' => 0,
            'msg' => 'Maximum No of Back ups reached. Please remove the old backups and try again.',
        ];
    }

    /**
     * Downloads a backup zip file.
     *
     * TODO: make it work no matter the flysystem driver (S3 Bucket, etc).
     */
    public function download($file)
    {
        if (!auth()->user()->can('backup')) {
            abort(403, 'Unauthorized action.');
        } 
        
        $filename = $file;

        $path = config('backupmanager.backups.backup_path') . DIRECTORY_SEPARATOR . $file;

        $file = Storage::disk(config('backupmanager.backups.disk'))->path('') . $path;
        
       
        $headers = array('Content-Type: application/gzip');
        $response = response()->download($file,$filename,$headers);
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        return $response;

    }
    
    public function restore($file)
    {
        if (!auth()->user()->can('backup')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $result = BackupManager::restoreBackups([$file]);

            FacadesLog::info('Backup restore response', ['result' => $result]);

            $restore_success = false;

            // Case 1: Array response with first index
            if (is_array($result) && isset($result[0])) {

                // Database restore
                if (isset($result[0]['d']) && $result[0]['d'] === true) {
                    $restore_success = true;
                }

                // File restore
                if (isset($result[0]['f']) && $result[0]['f'] === true) {
                    $restore_success = true;
                }

                // Generic success key
                if (isset($result[0]['success']) && $result[0]['success'] === true) {
                    $restore_success = true;
                }
            }

            // Case 2: Boolean true
            if ($result === true) {
                $restore_success = true;
            }

            if ($restore_success) {
                $business_id = request()->session()->get('user.business_id');
                $this->sendBackupRestoreSms($business_id, $file);

                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.success'),
                ];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => 'Backup restore failed. Please check logs.',
                ];
            }

        } catch (\Exception $e) {
            FacadesLog::error('Backup restore exception', [
                'file' => $file,
                'error' => $e->getMessage()
            ]);

            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return back()->with('status', $output);
    }


    /**
     * Deletes a backup file.
     */
    public function delete(Request $request, $file)
    {
        if (!auth()->user()->can('backup')) {
            abort(403, 'Unauthorized action.');
        }

        $filename = basename(urldecode($file));

        if ($filename === '' || $filename === '.' || $filename === '..') {
            $output = [
                'success' => 0,
                'msg' => 'Invalid backup file.',
            ];

            return $request->ajax()
                ? response()->json($output, 422)
                : redirect()->back()->with('status', $output);
        }

        try {
            $deleted = $this->deleteBackupFileDirectly($filename);

            if (!$deleted) {
                // Fallback for storage drivers / older backup manager records.
                try {
                    BackupManager::deleteBackups([$filename]);
                    $deleted = true;
                } catch (\Exception $e) {
                    FacadesLog::warning('BackupManager delete fallback failed', [
                        'file' => $filename,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $output = [
                'success' => $deleted ? 1 : 0,
                'msg' => $deleted ? 'Backup deleted successfully.' : 'Backup file not found or could not be deleted.',
                'file' => $filename,
            ];

            return $request->ajax()
                ? response()->json($output, $deleted ? 200 : 404)
                : redirect()->back()->with('status', $output);
        } catch (\Exception $e) {
            FacadesLog::error('Backup delete exception', [
                'file' => $filename,
                'error' => $e->getMessage(),
            ]);

            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
                'file' => $filename,
            ];

            return $request->ajax()
                ? response()->json($output, 500)
                : redirect()->back()->with('status', $output);
        }
    }

    private function deleteBackupFileDirectly($filename): bool
    {
        $disk = config('backupmanager.backups.disk');
        $backupPath = trim(config('backupmanager.backups.backup_path'), DIRECTORY_SEPARATOR);
        $relativePath = $backupPath === '' ? $filename : $backupPath . DIRECTORY_SEPARATOR . $filename;

        // Fast Laravel storage delete first.
        try {
            if (FacadesStorage::disk($disk)->exists($relativePath)) {
                return FacadesStorage::disk($disk)->delete($relativePath);
            }
        } catch (\Exception $e) {
            FacadesLog::warning('Storage delete failed for backup', [
                'file' => $filename,
                'error' => $e->getMessage(),
            ]);
        }

        // Direct filesystem fallback for local disks.
        try {
            $basePath = rtrim(FacadesStorage::disk($disk)->path(''), DIRECTORY_SEPARATOR);
            $fullPath = $basePath . DIRECTORY_SEPARATOR . $relativePath;
            $realBase = realpath($basePath);
            $realFile = realpath($fullPath);

            if ($realBase && $realFile && strpos($realFile, $realBase) === 0 && is_file($realFile)) {
                return @unlink($realFile);
            }
        } catch (\Exception $e) {
            FacadesLog::warning('Direct filesystem backup delete failed', [
                'file' => $filename,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
    // function store(Request $request)
    // {
    //     $uploadedFile = $request->file('backup');
        
    //     $backups = BackupManager::getBackups();
        
    //     $backupsNames = collect($backups)->pluck('name')->toArray();
        
    //     $backupSuffix = date(strtolower(config('backupmanager.backups.backup_file_date_suffix')));
        
    //     foreach($backupsNames as $backup){
    //         if(explode('.',$backup)[sizeof(explode('.',$backup))-1] == $uploadedFile->getClientOriginalExtension()){
    //             BackupManager::deleteBackups(array($backup));
    //         }
    //     }
        
        
    //     if($uploadedFile->getClientOriginalExtension() == 'tar'){
    //         $filename = "f_$backupSuffix.tar";
    //     }elseif($uploadedFile->getClientOriginalExtension() == 'gz'){
    //         $filename = "d_$backupSuffix.gz";
    //     }else{
    //         $filename = "d_$backupSuffix.$uploadedFile->getClientOriginalExtension()";
    //     }
        
    //     Storage::disk(config('backupmanager.backups.disk'))->putFileAs(
    //         config('backupmanager.backups.backup_path'),
    //         $uploadedFile,
    //         $filename
    //     );
    //     $message = 'Files Backup Upload Successfully';
    //     $messages[] = ['success' => 1,
    //         'msg' => __('lang_v1.success')
    //     ];
    //     return back()->with('status', $messages);
    // }

    // public function store(Request $request)
    // {
    //     if (!auth()->user()->can('backup.restore')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $request->validate([
    //         'backup' => 'required|file|mimes:gz,tar'
    //     ]);

    //     set_time_limit(0);

    //     $uploadedFile = $request->file('backup');

    //     $backupSuffix = date(strtolower(config('backupmanager.backups.backup_file_date_suffix')));
    //     $extension = $uploadedFile->getClientOriginalExtension();

    //     // Define filename exactly like system backups
    //     if ($extension === 'tar') {
    //         $filename = "f_{$backupSuffix}.tar";
    //     } else {
    //         $filename = "d_{$backupSuffix}.gz";
    //     }

    //     // Store uploaded backup
    //     Storage::disk(config('backupmanager.backups.disk'))->putFileAs(
    //         config('backupmanager.backups.backup_path'),
    //         $uploadedFile,
    //         $filename
    //     );

    //     // RESTORE DATABASE (key part)
    //     $result = BackupManager::restoreBackups([$filename]);

    //     if (!empty($result) && isset($result[0]['d']) && $result[0]['d'] === true) {

    //         $business_id = request()->session()->get('user.business_id');

    //         $this->sendBackupRestoreSms($business_id, $filename);

    //         $messages[] = [
    //             'success' => 1,
    //             'msg' => 'Database restored successfully from uploaded backup'
    //         ];
    //     } else {
    //         $messages[] = [
    //             'success' => 0,
    //             'msg' => 'Backup uploaded but restore failed'
    //         ];
    //     }

    //     return back()->with('status', $messages);
    // }\
    public function store(Request $request)
    {
        // permission for uploading a backup should be upload not restore
        if (!auth()->user()->can('backup.upload')) {
            abort(403, 'Unauthorized action.');
        }

        // validation: allow both gzip and tar files and a generous limit
        $request->validate([
            'backup' => 'required|file|mimes:gz,tar|max:'.(int)env('BACKUP_UPLOAD_MAX_KB', 512000), // default 500MB
        ]);

        set_time_limit(0);

        try {
            if ($this->hasReachedBackupRetentionLimit()) {
                return back()->with('status', $this->backupRetentionLimitOutput());
            }

            $uploadedFile = $request->file('backup');

            // generate a system compatible name similar to generated backups
            $backupSuffix = date(strtolower(config('backupmanager.backups.backup_file_date_suffix')));
            $extension = $uploadedFile->getClientOriginalExtension();

            if ($extension === 'tar') {
                $filename = "f_{$backupSuffix}.tar";
            } else {
                // treat anything else as database dump
                $filename = "d_{$backupSuffix}.{$extension}";
            }

            // store on configured disk & path
            Storage::disk(config('backupmanager.backups.disk'))->putFileAs(
                config('backupmanager.backups.backup_path'),
                $uploadedFile,
                $filename
            );

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.success'),
            ];

            return back()->with('status', $output);
        } catch (\Exception $e) {
            FacadesLog::error('Backup upload exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('status', [
                'success' => 0,
                'msg' => $e->getMessage(),
            ]);
        }
    }
         
    private function sendBackupRestoreSms($business_id, $backupFileName)
    {
        $business = Business::find($business_id);
        if (!$business) {
            return;
        }

        // Read saved settings
        $notify = $business->notify_backup_restore_sms ?? 'No';
        $numbers = $business->backup_sms_numbers ?? null;

        if ($notify !== 'Yes' || empty($numbers)) {
            return; //Feature disabled
        }

        // Validate numbers
        $validated = app(\App\Utils\TransactionUtil::class)->validateNos($numbers);
        $phones = $validated['valid'] ?? [];

        if (empty($phones)) {
            return;
        }

        $user = auth()->user();

        $message = "Backup Restored Successfully\n"
            . "Date Restored: " . now()->format('Y-m-d H:i') . "\n"
            . "User Restored: " . ($user->username ?? $user->name ?? 'System') . "\n"
            . "Backup Date: " . $backupFileName;

        foreach ($phones as $phone) {
            SmsLog::create([
                'business_id' => $business_id,
                'recipient'   => $phone,
                'message'     => $message,
                'sms_type'    => 'Backup Restore',
                'sms_status'  => 'Scheduled',
                'business_type' => 'business',
                'username'    => optional($user)->username,
                'uuid'        => rand(11111111111, 99999999999),
            ]);
        }
    }
}
