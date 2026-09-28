<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersistBannerStorage extends Command
{
    protected $signature = 'banners:persist-storage {--dry-run : Report what would be copied without changing files or database rows}';

    protected $description = 'Move central master banner images to persistent storage outside the Laravel deployment tree.';

    public function handle(): int
    {
        $connection = config('database.connections.system.database')
            ? 'system'
            : config('tenancy.database.central_connection', 'mysql');

        if (! Schema::connection($connection)->hasTable('banners')) {
            $this->error('Central banners table was not found. Nothing was changed.');
            return self::FAILURE;
        }

        $disk = Storage::disk('banner_uploads');
        $rows = DB::connection($connection)->table('banners')->orderBy('id')->get();
        $dryRun = (bool) $this->option('dry-run');
        $copied = 0;
        $already = 0;
        $missing = 0;
        $skipped = 0;

        $this->line('Persistent banner root: ' . config('filesystems.disks.banner_uploads.root'));
        $this->line('Master banner URL:      ' . config('filesystems.disks.banner_uploads.url'));
        $this->newLine();

        foreach ($rows as $banner) {
            if (($banner->storage_disk ?? 'local') === 's3') {
                $skipped++;
                $this->line("[SKIP] Banner {$banner->id}: S3 banner.");
                continue;
            }

            $storedPath = trim((string) ($banner->image_path ?? ''));
            if ($storedPath === '') {
                $missing++;
                $this->warn("[MISS] Banner {$banner->id}: empty image_path.");
                continue;
            }

            $key = $this->storageKey($storedPath);
            if ($key === null) {
                $missing++;
                $this->warn("[MISS] Banner {$banner->id}: path could not be resolved.");
                continue;
            }

            if ($disk->exists($key)) {
                $already++;
                $this->line("[OK]   Banner {$banner->id}: already persistent ({$key}).");
                if (! $dryRun) {
                    $this->updateRow($connection, $banner->id, $key);
                }
                continue;
            }

            $source = $this->findLegacySource($storedPath, $key);
            if ($source === null) {
                $missing++;
                $this->warn("[MISS] Banner {$banner->id}: source file not found ({$storedPath}).");
                continue;
            }

            $this->line(($dryRun ? '[DRY]  ' : '[COPY] ') . "Banner {$banner->id}: {$source} -> {$key}");

            if ($dryRun) {
                $copied++;
                continue;
            }

            $stream = fopen($source, 'rb');
            if ($stream === false) {
                $missing++;
                $this->warn("[MISS] Banner {$banner->id}: source could not be opened.");
                continue;
            }

            try {
                $disk->put($key, $stream);
            } finally {
                fclose($stream);
            }

            if (! $disk->exists($key)) {
                $missing++;
                $this->error("[FAIL] Banner {$banner->id}: persistent copy was not created.");
                continue;
            }

            $this->updateRow($connection, $banner->id, $key);
            $copied++;
        }

        $this->newLine();
        $this->info('Banner persistence check completed.');
        $this->line("Copied: {$copied} | Already persistent: {$already} | Missing: {$missing} | S3 skipped: {$skipped}");

        if ($dryRun) {
            $this->comment('Dry-run only: no file or database change was made.');
        } else {
            $this->comment('Legacy source files were left in place for rollback safety.');
        }

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function updateRow(string $connection, $id, string $key): void
    {
        DB::connection($connection)->table('banners')->where('id', $id)->update([
            'image_path' => $this->assetUrl($key),
            'storage_disk' => 'local',
            'updated_at' => now(),
        ]);
    }

    private function assetUrl(string $key): string
    {
        $base = rtrim((string) config('filesystems.disks.banner_uploads.url'), '/');
        $segments = array_map('rawurlencode', array_filter(explode('/', ltrim($key, '/')), 'strlen'));
        return $base . '/' . implode('/', $segments);
    }

    private function storageKey(string $storedPath): ?string
    {
        $path = trim($storedPath);

        if (preg_match('#^https?://#i', $path)) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($urlPath) ? $urlPath : '';
        }

        $path = ltrim(str_replace('\\', '/', rawurldecode($path)), '/');
        if ($path === '') {
            return null;
        }

        $masterPrefix = 'master-banner-assets/';
        $masterPos = strpos($path, $masterPrefix);
        if ($masterPos !== false) {
            return ltrim(substr($path, $masterPos + strlen($masterPrefix)), '/');
        }

        foreach (['public/storage/banners/', 'storage/banners/', 'public/uploads/', 'uploads/'] as $prefix) {
            if (Str::startsWith($path, $prefix)) {
                return ltrim(substr($path, strlen($prefix)), '/');
            }
        }

        $adsPos = strpos($path, 'ads/');
        if ($adsPos !== false) {
            return substr($path, $adsPos);
        }

        return $path;
    }

    private function findLegacySource(string $storedPath, string $key): ?string
    {
        $path = trim($storedPath);
        if (preg_match('#^https?://#i', $path)) {
            $urlPath = parse_url($path, PHP_URL_PATH);
            $path = is_string($urlPath) ? $urlPath : '';
        }

        $relative = ltrim(str_replace('\\', '/', rawurldecode($path)), '/');
        $candidates = [
            public_path($relative),
            public_path('uploads/' . $key),
            storage_path('app/public/banners/' . $key),
            public_path('storage/banners/' . $key),
            base_path($relative),
        ];

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
