<?php

namespace Modules\HelpGuide\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleAssignmentService
{
    private array $catalog;

    public function __construct()
    {
        $this->catalog = require __DIR__ . '/../Config/module_catalog.php';
    }

    public function catalog(): array
    {
        return $this->catalog;
    }

    public function businesses(string $connection): array
    {
        if (!Schema::connection($connection)->hasTable('business')) {
            return [];
        }
        $query = DB::connection($connection)->table('business')->select(['id', 'name']);
        if (Schema::connection($connection)->hasColumn('business', 'global_uid')) {
            $query->addSelect('global_uid');
        }
        if (Schema::connection($connection)->hasColumn('business', 'tenant_id')) {
            $query->addSelect('tenant_id');
        }
        return $query->orderBy('name')->get()->map(function ($b) {
            return [
                'id' => (int) $b->id,
                'name' => (string) ($b->name ?? ('Business #' . $b->id)),
                'uid' => (string) ($b->global_uid ?? ''),
                'tenant_id' => (string) ($b->tenant_id ?? ''),
            ];
        })->all();
    }

    public function assignment(string $connection, int $businessId): array
    {
        $enabled = [];
        $package = [];
        if (Schema::connection($connection)->hasTable('business')) {
            $bq = DB::connection($connection)->table('business')->where('id', $businessId);
            if (Schema::connection($connection)->hasColumn('business', 'enabled_modules')) {
                $raw = $bq->value('enabled_modules');
                $enabled = $this->decodeArray($raw);
            }
        }
        if (Schema::connection($connection)->hasTable('subscriptions')) {
            $sub = DB::connection($connection)->table('subscriptions')
                ->where('business_id', $businessId)
                ->orderByDesc('id')
                ->first();
            if ($sub && isset($sub->package_details)) {
                $package = $this->decodeArray($sub->package_details);
            }
        }

        $enabledNorm = array_fill_keys(array_map([$this, 'normalise'], array_map('strval', $enabled)), true);
        $result = [];
        foreach ($this->catalog as $key => $meta) {
            $aliases = array_values(array_unique(array_merge([$key, $key . '_module'], $meta['aliases'] ?? [])));
            $assigned = false;
            foreach ($aliases as $alias) {
                $norm = $this->normalise((string) $alias);
                if (isset($enabledNorm[$norm])) {
                    $assigned = true;
                    break;
                }
                foreach ([$alias, $norm, str_replace('_', '', $norm)] as $candidate) {
                    if (array_key_exists($candidate, $package) && $this->truthy($package[$candidate])) {
                        $assigned = true;
                        break 2;
                    }
                }
            }
            $result[$key] = $assigned;
        }
        return $result;
    }

    private function decodeArray($value): array
    {
        if (is_array($value)) return $value;
        if (is_object($value)) return (array) $value;
        if (!is_string($value) || trim($value) === '') return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalise(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';
        return trim($value, '_');
    }

    private function truthy($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (float) $value > 0;
        if (is_array($value)) return count($value) > 0;
        $v = strtolower(trim((string) $value));
        return !in_array($v, ['', '0', 'false', 'off', 'no', 'null'], true);
    }
}
