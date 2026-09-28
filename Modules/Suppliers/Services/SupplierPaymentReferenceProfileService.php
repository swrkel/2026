<?php

namespace Modules\Suppliers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SupplierPaymentReferenceProfileService
{
    public const TABLE = 'supplier_payment_reference_prefixes';

    /**
     * System codes stay stable so Purchase/Supplier code does not need to know
     * the user's chosen visible prefix.
     */
    public const CONTEXTS = [
        'SLP' => [
            'key' => 'supplier_pay_due',
            'label' => 'Supplier List / Pay Due Amount',
            'default_prefix' => 'SLP',
        ],
        'APEP' => [
            'key' => 'purchase_entry_payment',
            'label' => 'Purchase / Add Purchase Order Entry / Payment',
            'default_prefix' => 'APEP',
        ],
        'LPEP' => [
            'key' => 'purchase_additional_payment',
            'label' => 'Purchase / List Purchase / Action / Add Payment',
            'default_prefix' => 'LPEP',
        ],
    ];

    public function available(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    public function contexts(): array
    {
        return self::CONTEXTS;
    }

    public function ensureDefaults(int $businessId, ?int $userId = null): void
    {
        if ($businessId <= 0 || ! $this->available()) {
            return;
        }

        foreach (self::CONTEXTS as $systemCode => $meta) {
            $hasActive = DB::table(self::TABLE)
                ->where('business_id', $businessId)
                ->where('context_key', $meta['key'])
                ->where('is_active', 1)
                ->exists();

            if ($hasActive) {
                continue;
            }

            $candidate = DB::table(self::TABLE)
                ->where('business_id', $businessId)
                ->where('context_key', $meta['key'])
                ->orderByDesc('id')
                ->first();

            if ($candidate) {
                DB::table(self::TABLE)->where('id', $candidate->id)->update([
                    'is_active' => 1,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
                continue;
            }

            DB::table(self::TABLE)->insert([
                'business_id' => $businessId,
                'context_key' => $meta['key'],
                'context_label' => $meta['label'],
                'prefix' => $meta['default_prefix'],
                'starting_number' => 1,
                'number_length' => 4,
                'is_active' => 1,
                'used_count' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function rows(int $businessId): array
    {
        $this->ensureDefaults($businessId, $this->userId());
        if (! $this->available()) {
            return [];
        }

        return DB::table(self::TABLE)
            ->where('business_id', $businessId)
            ->orderBy('context_label')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) use ($businessId) {
                $row->is_used = $this->isUsed($row, $businessId);
                return $row;
            })->all();
    }

    public function activeForSystemCode(string $systemCode, int $businessId, bool $lock = false): ?object
    {
        $systemCode = strtoupper(trim($systemCode));
        $meta = self::CONTEXTS[$systemCode] ?? null;
        if (! $meta || ! $this->available()) {
            return null;
        }

        $this->ensureDefaults($businessId, $this->userId());
        $query = DB::table(self::TABLE)
            ->where('business_id', $businessId)
            ->where('context_key', $meta['key'])
            ->where('is_active', 1)
            ->orderByDesc('id');

        if ($lock && DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function store(int $businessId, array $data, ?int $userId = null): int
    {
        $contextKey = (string) ($data['context_key'] ?? '');
        $meta = $this->metaByKey($contextKey);
        if (! $meta) {
            throw ValidationException::withMessages(['context_key' => 'Please select a valid payment source.']);
        }

        $prefix = $this->normalisePrefix((string) ($data['prefix'] ?? ''));
        $starting = max(1, (int) ($data['starting_number'] ?? 1));
        $this->assertUniquePrefix($businessId, $prefix, null);

        return DB::transaction(function () use ($businessId, $contextKey, $meta, $prefix, $starting, $userId): int {
            DB::table(self::TABLE)
                ->where('business_id', $businessId)
                ->where('context_key', $contextKey)
                ->where('is_active', 1)
                ->update(['is_active' => 0, 'updated_by' => $userId, 'updated_at' => now()]);

            return (int) DB::table(self::TABLE)->insertGetId([
                'business_id' => $businessId,
                'context_key' => $contextKey,
                'context_label' => $meta['label'],
                'prefix' => $prefix,
                'starting_number' => $starting,
                'number_length' => max(4, strlen((string) $starting)),
                'is_active' => 1,
                'used_count' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    public function update(int $businessId, int $id, array $data, ?int $userId = null): void
    {
        $row = $this->row($businessId, $id);
        if ($this->isUsed($row, $businessId)) {
            throw ValidationException::withMessages(['prefix' => 'This prefix has already been used in a transaction and cannot be edited.']);
        }

        $prefix = $this->normalisePrefix((string) ($data['prefix'] ?? ''));
        $starting = max(1, (int) ($data['starting_number'] ?? 1));
        $this->assertUniquePrefix($businessId, $prefix, $id);

        DB::table(self::TABLE)->where('id', $id)->where('business_id', $businessId)->update([
            'prefix' => $prefix,
            'starting_number' => $starting,
            'number_length' => max(4, strlen((string) $starting)),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    public function delete(int $businessId, int $id): void
    {
        $row = $this->row($businessId, $id);
        if ($this->isUsed($row, $businessId)) {
            throw ValidationException::withMessages(['prefix' => 'This prefix has already been used in a transaction and cannot be deleted.']);
        }

        DB::transaction(function () use ($businessId, $row): void {
            DB::table(self::TABLE)->where('id', $row->id)->where('business_id', $businessId)->delete();
            if ((int) $row->is_active === 1) {
                $replacement = DB::table(self::TABLE)
                    ->where('business_id', $businessId)
                    ->where('context_key', $row->context_key)
                    ->orderByDesc('id')
                    ->first();
                if ($replacement) {
                    DB::table(self::TABLE)->where('id', $replacement->id)->update(['is_active' => 1, 'updated_at' => now()]);
                }
            }
        }, 3);

        $this->ensureDefaults($businessId, $this->userId());
    }

    public function markUsed(int $id): void
    {
        if ($id <= 0 || ! $this->available()) {
            return;
        }
        DB::table(self::TABLE)->where('id', $id)->update([
            'used_count' => DB::raw('used_count + 1'),
            'first_used_at' => DB::raw('COALESCE(first_used_at, CURRENT_TIMESTAMP)'),
            'last_used_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function matches(?string $reference, ?string $systemCode, int $businessId): bool
    {
        $reference = trim((string) $reference);
        if ($reference === '' || ! $this->available()) {
            return false;
        }

        $query = DB::table(self::TABLE)->where('business_id', $businessId);
        if ($systemCode !== null) {
            $meta = self::CONTEXTS[strtoupper(trim($systemCode))] ?? null;
            if (! $meta) {
                return false;
            }
            $query->where('context_key', $meta['key']);
        }

        foreach ($query->get(['prefix']) as $row) {
            if ($this->referenceMatchesPrefix($reference, (string) $row->prefix)) {
                return true;
            }
        }
        return false;
    }

    public function isUsed(object $row, int $businessId): bool
    {
        if ((int) ($row->used_count ?? 0) > 0) {
            return true;
        }
        if (! Schema::hasTable('transaction_payments') || ! Schema::hasColumn('transaction_payments', 'payment_ref_no')) {
            return false;
        }
        $prefix = (string) ($row->prefix ?? '');
        if ($prefix === '') {
            return false;
        }

        $query = DB::table('transaction_payments')
            ->where('business_id', $businessId)
            ->where('payment_ref_no', 'like', $prefix . '%');
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        foreach ($query->pluck('payment_ref_no') as $reference) {
            if ($this->referenceMatchesPrefix((string) $reference, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function row(int $businessId, int $id): object
    {
        $row = DB::table(self::TABLE)->where('business_id', $businessId)->where('id', $id)->first();
        if (! $row) {
            abort(404);
        }
        return $row;
    }

    private function metaByKey(string $key): ?array
    {
        foreach (self::CONTEXTS as $meta) {
            if ($meta['key'] === $key) {
                return $meta;
            }
        }
        return null;
    }

    private function assertUniquePrefix(int $businessId, string $prefix, ?int $ignoreId): void
    {
        $query = DB::table(self::TABLE)->where('business_id', $businessId)->whereRaw('UPPER(prefix) = ?', [$prefix]);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['prefix' => 'This prefix already exists in Supplier Payment Reference settings.']);
        }
    }

    private function normalisePrefix(string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/\s+/', '', trim($prefix)) ?? '');
        if ($prefix === '' || strlen($prefix) > 20 || ! preg_match('/^[A-Z0-9\-\/]+$/', $prefix)) {
            throw ValidationException::withMessages(['prefix' => 'Prefix is required and may contain only letters, numbers, hyphen or slash (maximum 20 characters).']);
        }
        return $prefix;
    }

    private function referenceMatchesPrefix(string $reference, string $prefix): bool
    {
        return (bool) preg_match('/^' . preg_quote($prefix, '/') . '\d{4}-\d+$/', trim($reference));
    }

    private function userId(): ?int
    {
        return auth()->check() ? (int) auth()->id() : null;
    }
}
