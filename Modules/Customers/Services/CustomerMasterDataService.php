<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerMasterDataService
{
    protected $allowedTables = [
        'types' => 'customer_types',
        'categories' => 'customer_categories',
        'classifications' => 'customer_classifications',
        'custom_fields' => 'customer_custom_fields',
        'settings' => 'customer_module_settings',
        'opening_balances' => 'customer_opening_balance_adjustments',
    ];

    public function businessId(): int
    {
        return (int) (request()->session()->get('business.id') ?: request()->session()->get('user.business_id'));
    }

    public function tableName(string $key): string
    {
        return $this->table($key);
    }

    public function isAvailable(string $key): bool
    {
        return Schema::hasTable($this->table($key));
    }

    public function list(string $key)
    {
        $table = $this->table($key);
        if (!Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)
            ->where('business_id', $this->businessId())
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    public function find(string $key, int $id)
    {
        $table = $this->table($key);
        if (!Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)
            ->where('business_id', $this->businessId())
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    }

    public function create(string $key, array $data): int
    {
        $table = $this->table($key);
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $payload = $this->payload($key, $data);
        $payload['business_id'] = $this->businessId();
        $payload['created_by'] = optional(auth()->user())->id;
        $payload['created_at'] = now();
        $payload['updated_at'] = now();

        return DB::table($table)->insertGetId($payload);
    }

    public function update(string $key, int $id, array $data): void
    {
        $table = $this->table($key);
        if (!Schema::hasTable($table)) {
            return;
        }

        $payload = $this->payload($key, $data);
        $payload['updated_at'] = now();

        DB::table($table)
            ->where('business_id', $this->businessId())
            ->where('id', $id)
            ->update($payload);
    }

    public function delete(string $key, int $id): void
    {
        $table = $this->table($key);
        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->where('business_id', $this->businessId())
            ->where('id', $id)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    protected function table(string $key): string
    {
        if (! isset($this->allowedTables[$key])) {
            abort(404);
        }

        return $this->allowedTables[$key];
    }

    protected function payload(string $key, array $data): array
    {
        if ($key === 'settings') {
            return [
                'name' => trim($data['name'] ?? 'Default'),
                'value' => trim($data['value'] ?? ''),
                'description' => trim($data['description'] ?? ''),
                'is_active' => ! empty($data['is_active']) ? 1 : 0,
            ];
        }

        if ($key === 'opening_balances') {
            return [
                'name' => trim($data['name'] ?? 'Opening Balance Adjustment'),
                'reference_no' => trim($data['reference_no'] ?? ''),
                'amount' => (float) ($data['amount'] ?? 0),
                'transaction_date' => ! empty($data['transaction_date']) ? $data['transaction_date'] : date('Y-m-d'),
                'description' => trim($data['description'] ?? ''),
                'is_active' => ! empty($data['is_active']) ? 1 : 0,
            ];
        }

        return [
            'name' => trim($data['name'] ?? ''),
            'code' => trim($data['code'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'is_active' => ! empty($data['is_active']) ? 1 : 0,
        ];
    }
}
