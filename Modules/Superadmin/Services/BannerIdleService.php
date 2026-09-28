<?php

namespace Modules\Superadmin\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Central configuration + runtime banner resolver for task 8051.
 *
 * The configuration is stored only in the central database. Runtime banner
 * content is deliberately assembled from two places:
 *   1. central banners that apply to the current tenant; and
 *   2. banners created directly inside the current tenant database.
 *
 * No banner rows are copied between databases and no existing banner table is
 * modified, which keeps the feature isolated from the current banner manager.
 */
class BannerIdleService
{
    public const SETTINGS_TABLE = 'banner_idle_settings';
    public const ALL = '__all__';

    /**
     * Safe default: targeting is All/All, but idle display stays disabled until
     * a Super Admin enters a positive number of minutes.
     */
    public function settings(): array
    {
        $defaults = [
            'idle_seconds' => 0,
            'idle_minutes' => 0,
            'image_size_percent' => 90,
            'all_tenants' => true,
            'tenant_ids' => [],
            'all_businesses' => true,
            'business_targets' => [],
        ];

        try {
            $connection = CentralContext::trueCentralConnectionName();
            $schema = Schema::connection($connection);

            if (! $schema->hasTable(self::SETTINGS_TABLE)) {
                return $defaults;
            }

            $row = DB::connection($connection)
                ->table(self::SETTINGS_TABLE)
                ->where('id', 1)
                ->first();

            if (empty($row)) {
                return $defaults;
            }

            // 8051: seconds are the canonical unit. If this code is deployed
            // before the add-idle-seconds migration, preserve the previous
            // whole-minute value so the page remains readable until migration.
            $idleSeconds = $schema->hasColumn(self::SETTINGS_TABLE, 'idle_seconds')
                ? max(0, (int) ($row->idle_seconds ?? 0))
                : max(0, (int) ($row->idle_minutes ?? 0)) * 60;

            $imageSizePercent = $schema->hasColumn(self::SETTINGS_TABLE, 'image_size_percent')
                ? min(100, max(25, (int) ($row->image_size_percent ?? 90)))
                : 90;

            return [
                'idle_seconds' => $idleSeconds,
                'idle_minutes' => $idleSeconds / 60,
                'image_size_percent' => $imageSizePercent,
                'all_tenants' => (bool) ($row->all_tenants ?? true),
                'tenant_ids' => $this->decodeList($row->tenant_ids ?? null),
                'all_businesses' => (bool) ($row->all_businesses ?? true),
                'business_targets' => $this->decodeList($row->business_targets ?? null),
            ];
        } catch (\Throwable $e) {
            Log::warning('Idle banner settings could not be read.', [
                'message' => $e->getMessage(),
            ]);

            return $defaults;
        }
    }

    /**
     * Save one global central setting. All tenant/business inputs are validated
     * against the current central registries before being persisted.
     */
    public function saveSettings(int $idleSeconds, int $imageSizePercent, array $tenantIds, array $businessTargets): array
    {
        $connection = CentralContext::trueCentralConnectionName();
        $schema = Schema::connection($connection);

        if (! $schema->hasTable(self::SETTINGS_TABLE)) {
            throw new \RuntimeException(
                'The banner_idle_settings table is missing. Run the Superadmin migrations (or the supplied SQL) first.'
            );
        }

        if (! $schema->hasColumn(self::SETTINGS_TABLE, 'idle_seconds')) {
            throw new \RuntimeException(
                'The idle_seconds column is missing. Run the latest Superadmin migration before saving Banners Management.'
            );
        }

        if (! $schema->hasColumn(self::SETTINGS_TABLE, 'image_size_percent')) {
            throw new \RuntimeException(
                'The image_size_percent column is missing. Run the latest Superadmin migration before saving Banners Management.'
            );
        }

        $imageSizePercent = min(100, max(25, $imageSizePercent));

        $tenantIds = $this->cleanStrings($tenantIds);
        $allTenants = $tenantIds === [] || in_array(self::ALL, $tenantIds, true);

        $validTenantIds = array_column($this->tenantOptions(), 'id');
        $tenantIds = $allTenants
            ? []
            : array_values(array_intersect($tenantIds, $validTenantIds));

        // An empty specific selection is safer as All than as an accidental
        // configuration that can never match any user.
        if (! $allTenants && $tenantIds === []) {
            $allTenants = true;
        }

        $businessTargets = $this->cleanStrings($businessTargets);
        $allBusinesses = $businessTargets === [] || in_array(self::ALL, $businessTargets, true);

        if (! $allBusinesses) {
            $validBusinessTargets = array_column(
                $this->businessOptions($allTenants ? [self::ALL] : $tenantIds),
                'value'
            );
            $businessTargets = array_values(array_intersect($businessTargets, $validBusinessTargets));

            if ($businessTargets === []) {
                $allBusinesses = true;
            }
        }

        if ($allBusinesses) {
            $businessTargets = [];
        }

        $now = now()->toDateTimeString();
        $table = DB::connection($connection)->table(self::SETTINGS_TABLE);
        $data = [
            'idle_seconds' => max(0, $idleSeconds),
            // Keep the legacy whole-minute column synchronized for older code.
            'idle_minutes' => $idleSeconds > 0 ? max(1, (int) ceil($idleSeconds / 60)) : 0,
            'image_size_percent' => $imageSizePercent,
            'all_tenants' => $allTenants ? 1 : 0,
            'tenant_ids' => $allTenants ? null : json_encode($tenantIds),
            'all_businesses' => $allBusinesses ? 1 : 0,
            'business_targets' => $allBusinesses ? null : json_encode($businessTargets),
            'updated_at' => $now,
        ];

        if ($table->where('id', 1)->exists()) {
            DB::connection($connection)
                ->table(self::SETTINGS_TABLE)
                ->where('id', 1)
                ->update($data);
        } else {
            $data['id'] = 1;
            $data['created_at'] = $now;
            DB::connection($connection)
                ->table(self::SETTINGS_TABLE)
                ->insert($data);
        }

        return $this->settings();
    }

    /**
     * Tenant select options: "Database Name (Tenant UID)".
     */
    public function tenantOptions(): array
    {
        try {
            $connection = CentralContext::trueCentralConnectionName();
            $schema = Schema::connection($connection);
            if (! $schema->hasTable('tenants')) {
                return [];
            }

            $rows = DB::connection($connection)
                ->table('tenants')
                ->select(['id', 'data'])
                ->orderBy('id')
                ->get();

            $options = [];
            foreach ($rows as $row) {
                $tenantUid = trim((string) ($row->id ?? ''));
                if ($tenantUid === '') {
                    continue;
                }

                $data = $this->decodeObject($row->data ?? null);
                $database = trim((string) ($data['tenancy_db_name'] ?? ''));
                if ($database === '') {
                    $database = $this->configuredTenantDatabaseName($tenantUid);
                }

                $options[] = [
                    'id' => $tenantUid,
                    'database' => $database,
                    'label' => $database . ' (' . $tenantUid . ')',
                ];
            }

            return $options;
        } catch (\Throwable $e) {
            Log::warning('Idle banner tenant options could not be loaded.', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Business options are central registry rows. Values use global_uid when
     * available so a tenant-local business can be matched even when its local
     * numeric id differs from the central registry id.
     */
    public function businessOptions(array $tenantIds): array
    {
        try {
            $connection = CentralContext::trueCentralConnectionName();
            $schema = Schema::connection($connection);
            if (! $schema->hasTable('business') || ! $schema->hasColumn('business', 'tenant_id')) {
                return [];
            }

            $tenantIds = $this->cleanStrings($tenantIds);
            $allTenants = $tenantIds === [] || in_array(self::ALL, $tenantIds, true);

            $columns = ['id', 'name', 'tenant_id'];
            foreach (['global_uid', 'company_number'] as $optional) {
                if ($schema->hasColumn('business', $optional)) {
                    $columns[] = $optional;
                }
            }

            $query = DB::connection($connection)
                ->table('business')
                ->select($columns)
                ->whereNotNull('tenant_id')
                ->where('tenant_id', '<>', '');

            if (! $allTenants) {
                $query->whereIn('tenant_id', $tenantIds);
            }

            $rows = $query
                ->orderBy('tenant_id')
                ->orderBy('name')
                ->get();

            $options = [];
            foreach ($rows as $row) {
                $tenantId = trim((string) ($row->tenant_id ?? ''));
                if ($tenantId === '') {
                    continue;
                }

                $globalUid = trim((string) ($row->global_uid ?? ''));
                $value = $globalUid !== ''
                    ? $tenantId . '::uid::' . $globalUid
                    : $tenantId . '::id::' . (int) $row->id;

                $name = trim((string) ($row->name ?? ''));
                if ($name === '') {
                    $name = 'Business ' . (int) $row->id;
                }

                $suffix = ! empty($row->company_number)
                    ? ' · ' . (string) $row->company_number
                    : ' · ID ' . (int) $row->id;

                $options[] = [
                    'value' => $value,
                    'tenant_id' => $tenantId,
                    'business_id' => (int) $row->id,
                    'global_uid' => $globalUid,
                    'label' => '[' . $tenantId . '] ' . $name . $suffix,
                ];
            }

            return $options;
        } catch (\Throwable $e) {
            Log::warning('Idle banner business options could not be loaded.', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Public runtime payload consumed by the small browser-side idle viewer.
     */
    public function runtimePayload(): array
    {
        $settings = $this->settings();
        $idleSeconds = max(0, (int) ($settings['idle_seconds'] ?? 0));
        $idleMinutes = $idleSeconds / 60;
        $imageSizePercent = min(100, max(25, (int) ($settings['image_size_percent'] ?? 90)));
        $tenantId = $this->currentTenantId();

        if ($idleSeconds <= 0 || $tenantId === null) {
            return [
                'enabled' => false,
                'idle_seconds' => $idleSeconds,
                'idle_minutes' => $idleMinutes,
                'image_size_percent' => $imageSizePercent,
                'banners' => [],
            ];
        }

        if (! $this->appliesToCurrentUser($settings, $tenantId)) {
            return [
                'enabled' => false,
                'idle_seconds' => $idleSeconds,
                'idle_minutes' => $idleMinutes,
                'image_size_percent' => $imageSizePercent,
                'banners' => [],
            ];
        }

        if ($this->centralBannerPauseActive()) {
            return [
                'enabled' => true,
                'idle_seconds' => $idleSeconds,
                'idle_minutes' => $idleMinutes,
                'image_size_percent' => $imageSizePercent,
                'banners' => [],
            ];
        }

        $central = $this->centralBanners($tenantId);
        $tenant = $this->tenantBanners();
        $banners = $this->deduplicateBanners(array_merge($central, $tenant));

        return [
            'enabled' => true,
            'idle_seconds' => $idleSeconds,
            'idle_minutes' => $idleMinutes,
            'image_size_percent' => $imageSizePercent,
            'banners' => array_values($banners),
        ];
    }

    private function appliesToCurrentUser(array $settings, string $tenantId): bool
    {
        if (empty($settings['all_tenants'])) {
            $tenantIds = $this->cleanStrings((array) ($settings['tenant_ids'] ?? []));
            if (! in_array($tenantId, $tenantIds, true)) {
                return false;
            }
        }

        if (! empty($settings['all_businesses'])) {
            return true;
        }

        $targets = $this->cleanStrings((array) ($settings['business_targets'] ?? []));
        if ($targets === []) {
            return false;
        }

        $businessId = (int) (session()->get('user.business_id') ?: session()->get('business.id'));
        if ($businessId <= 0) {
            return false;
        }

        $candidates = [
            $tenantId . '::id::' . $businessId,
        ];

        try {
            $tenantConnection = DB::connection();
            $tenantSchema = $tenantConnection->getSchemaBuilder();
            $localBusiness = null;

            if ($tenantSchema->hasTable('business')) {
                $localColumns = ['id', 'name'];
                foreach (['global_uid', 'company_number'] as $column) {
                    if ($tenantSchema->hasColumn('business', $column)) {
                        $localColumns[] = $column;
                    }
                }

                $localBusiness = $tenantConnection->table('business')
                    ->select($localColumns)
                    ->where('id', $businessId)
                    ->first();
            }

            $globalUid = trim((string) ($localBusiness->global_uid ?? ''));
            if ($globalUid !== '') {
                array_unshift($candidates, $tenantId . '::uid::' . $globalUid);
            }

            // Older tenant databases may not yet have global_uid. In that case
            // map the current local business back to its central registry row
            // using tenant_id + company number (preferred) or name, then add
            // the central id target used by the Manage New dropdown fallback.
            $centralConnectionName = CentralContext::trueCentralConnectionName();
            $centralSchema = Schema::connection($centralConnectionName);
            if ($localBusiness
                && $centralSchema->hasTable('business')
                && $centralSchema->hasColumn('business', 'tenant_id')) {
                $centralQuery = DB::connection($centralConnectionName)
                    ->table('business')
                    ->where('tenant_id', $tenantId);

                $matched = false;
                if ($globalUid !== '' && $centralSchema->hasColumn('business', 'global_uid')) {
                    $centralQuery->where('global_uid', $globalUid);
                    $matched = true;
                } elseif (! empty($localBusiness->company_number)
                    && $centralSchema->hasColumn('business', 'company_number')) {
                    $centralQuery->where('company_number', (string) $localBusiness->company_number);
                    $matched = true;
                } elseif (! empty($localBusiness->name)) {
                    $centralQuery->where('name', (string) $localBusiness->name);
                    $matched = true;
                }

                if ($matched) {
                    $centralBusinessId = (int) $centralQuery->value('id');
                    if ($centralBusinessId > 0) {
                        $candidates[] = $tenantId . '::id::' . $centralBusinessId;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Keep the basic numeric candidate. Target matching should fail
            // closed rather than making a normal tenant screen unavailable.
        }

        $candidates = array_values(array_unique($candidates));
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $targets, true)) {
                return true;
            }
        }

        return false;
    }

    private function currentTenantId(): ?string
    {
        try {
            if (function_exists('tenant') && tenant()) {
                $id = trim((string) tenant()->getTenantKey());
                return $id !== '' ? $id : null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function centralBanners(string $tenantId): array
    {
        try {
            $connectionName = CentralContext::trueCentralConnectionName();
            $connection = DB::connection($connectionName);
            $schema = $connection->getSchemaBuilder();

            if (! $schema->hasTable('banners')) {
                return [];
            }

            $query = $connection->table('banners');
            if ($schema->hasColumn('banners', 'is_active')) {
                $query->where('is_active', 1);
            }

            if ($schema->hasColumn('banners', 'created_at')) {
                $query->orderBy('created_at');
            } elseif ($schema->hasColumn('banners', 'id')) {
                $query->orderBy('id');
            }

            $rows = $query->get();
            if ($rows->isEmpty()) {
                return [];
            }

            $associations = collect();
            if ($schema->hasTable('banner_tenants')) {
                $ids = $rows->pluck('id')->filter()->values()->all();
                if ($ids !== []) {
                    $associations = $connection->table('banner_tenants')
                        ->whereIn('banner_id', $ids)
                        ->get()
                        ->groupBy('banner_id');
                }
            }

            $output = [];
            foreach ($rows as $row) {
                $bannerAssociations = $associations->get($row->id, collect());
                if ($bannerAssociations->isNotEmpty()) {
                    $allowed = $bannerAssociations->contains(static function ($assoc) use ($tenantId) {
                        return (string) ($assoc->tenant_id ?? '') === $tenantId;
                    });
                    if (! $allowed) {
                        continue;
                    }
                }

                $normalized = $this->normalizeBanner($row, 'central');
                if ($normalized !== null) {
                    $output[] = $normalized;
                }
            }

            return $output;
        } catch (\Throwable $e) {
            Log::warning('Central idle banners could not be loaded.', [
                'tenant_id' => $tenantId,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function tenantBanners(): array
    {
        try {
            $tenantConnection = DB::connection();
            $tenantDatabase = (string) $tenantConnection->getDatabaseName();
            $centralConnection = DB::connection(CentralContext::trueCentralConnectionName());
            $centralDatabase = (string) $centralConnection->getDatabaseName();

            if ($tenantDatabase === '' || $tenantDatabase === $centralDatabase) {
                return [];
            }

            $schema = $tenantConnection->getSchemaBuilder();
            if (! $schema->hasTable('banners')) {
                return [];
            }

            $query = $tenantConnection->table('banners');
            if ($schema->hasColumn('banners', 'is_active')) {
                $query->where('is_active', 1);
            } elseif ($schema->hasColumn('banners', 'status')) {
                $query->where('status', 1);
            }
            if ($schema->hasColumn('banners', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if ($schema->hasColumn('banners', 'created_at')) {
                $query->orderBy('created_at');
            } elseif ($schema->hasColumn('banners', 'id')) {
                $query->orderBy('id');
            }

            $output = [];
            foreach ($query->get() as $row) {
                $normalized = $this->normalizeBanner($row, 'tenant');
                if ($normalized !== null) {
                    $output[] = $normalized;
                }
            }

            return $output;
        } catch (\Throwable $e) {
            Log::warning('Tenant idle banners could not be loaded.', [
                'tenant_id' => $this->currentTenantId(),
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function normalizeBanner($row, string $source): ?array
    {
        $imagePath = '';
        foreach (['image_path', 'image', 'banner_image', 'content'] as $field) {
            if (isset($row->{$field}) && trim((string) $row->{$field}) !== '') {
                $imagePath = trim((string) $row->{$field});
                break;
            }
        }

        if ($imagePath === '') {
            return null;
        }

        $title = '';
        foreach (['title', 'name'] as $field) {
            if (isset($row->{$field}) && trim((string) $row->{$field}) !== '') {
                $title = trim((string) $row->{$field});
                break;
            }
        }

        $duration = 5;
        foreach (['display_duration', 'duration'] as $field) {
            if (isset($row->{$field}) && (int) $row->{$field} > 0) {
                $duration = (int) $row->{$field};
                break;
            }
        }

        $link = null;
        foreach (['link_url', 'url'] as $field) {
            if (isset($row->{$field}) && trim((string) $row->{$field}) !== '') {
                $link = trim((string) $row->{$field});
                break;
            }
        }
        if ($link !== null && ! preg_match('#^https?://#i', $link)) {
            $link = null;
        }

        return [
            'id' => isset($row->id) ? (string) $row->id : md5($imagePath),
            'source' => $source,
            'code' => isset($row->banner_code) ? (string) $row->banner_code : null,
            'title' => $title,
            'image_url' => $this->resolveBannerImageUrl($imagePath, (string) ($row->storage_disk ?? 'local')),
            'display_duration' => max(1, $duration),
            'link_url' => $link,
        ];
    }

    private function resolveBannerImageUrl(string $path, string $storageDisk): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (Str::startsWith($path, '//')) {
            return request()->getScheme() . ':' . $path;
        }

        $path = str_replace('\\', '/', $path);
        $relative = ltrim($path, '/');
        if (Str::startsWith($relative, 'public/')) {
            $relative = substr($relative, strlen('public/'));
        }

        if ($storageDisk === 's3' && env('AWS_SUPERADMIN_AD_STORAGE_ENABLED')) {
            try {
                return Storage::disk('s3')->url($relative);
            } catch (\Throwable $e) {
                // Continue to public/local resolution.
            }
        }

        if (Str::startsWith($relative, ['uploads/', 'storage/', 'img/'])) {
            return asset($relative);
        }

        try {
            if (Storage::disk('banner_uploads')->exists($relative)) {
                $base = rtrim((string) config('filesystems.disks.banner_uploads.url'), '/');
                $segments = array_map('rawurlencode', array_filter(explode('/', $relative), 'strlen'));
                return $base . '/' . implode('/', $segments);
            }
        } catch (\Throwable $e) {
            // Fall through to legacy public uploads.
        }

        try {
            $diskUrl = Storage::disk('public_uploads')->url($relative);
            if (preg_match('#^https?://#i', $diskUrl)) {
                return $diskUrl;
            }

            return asset(ltrim($diskUrl, '/'));
        } catch (\Throwable $e) {
            return asset($relative);
        }
    }

    private function centralBannerPauseActive(): bool
    {
        try {
            if (! class_exists('\App\BannerSetting')) {
                return false;
            }

            $model = new \App\BannerSetting();
            $table = $model->getTable();
            $connectionName = CentralContext::trueCentralConnectionName();
            $schema = Schema::connection($connectionName);
            if (! $schema->hasTable($table) || ! $schema->hasColumn($table, 'pause_until')) {
                return false;
            }

            $pauseUntil = DB::connection($connectionName)->table($table)->value('pause_until');
            return ! empty($pauseUntil) && Carbon::parse($pauseUntil)->isFuture();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function deduplicateBanners(array $banners): array
    {
        $unique = [];
        $seen = [];

        foreach ($banners as $banner) {
            $code = trim((string) ($banner['code'] ?? ''));
            $key = $code !== ''
                ? 'code:' . strtolower($code)
                : 'img:' . strtolower(trim((string) ($banner['image_url'] ?? ''))) . '|title:' . strtolower(trim((string) ($banner['title'] ?? '')));

            if ($key === 'img:|title:' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $banner;
        }

        return $unique;
    }

    private function configuredTenantDatabaseName(string $tenantUid): string
    {
        return (string) config('tenancy.database.prefix', '')
            . $tenantUid
            . (string) config('tenancy.database.suffix', '');
    }

    private function cleanStrings(array $values): array
    {
        $clean = [];
        foreach ($values as $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $clean[$value] = true;
            }
        }

        return array_keys($clean);
    }

    private function decodeList($value): array
    {
        if (is_array($value)) {
            return $this->cleanStrings($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $this->cleanStrings($decoded) : [];
    }

    private function decodeObject($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
