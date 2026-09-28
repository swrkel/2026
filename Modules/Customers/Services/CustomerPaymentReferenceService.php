<?php

namespace Modules\Customers\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CustomerPaymentReferenceService
{
    public const TABLE = 'customer_payment_reference_prefixes';

    public const CONTEXTS = [
        'customer_pay_due' => [
            'label' => 'Customer Register / Action / Pay Due Amount',
            'default_prefix' => 'CPD',
        ],
        'customer_payments' => [
            'label' => 'Customer Payments',
            'default_prefix' => 'CPM',
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
        foreach (self::CONTEXTS as $key => $meta) {
            if (DB::table(self::TABLE)->where('business_id', $businessId)->where('context_key', $key)->where('is_active', 1)->exists()) {
                continue;
            }
            $old = DB::table(self::TABLE)->where('business_id', $businessId)->where('context_key', $key)->orderByDesc('id')->first();
            if ($old) {
                DB::table(self::TABLE)->where('id', $old->id)->update(['is_active' => 1, 'updated_by' => $userId, 'updated_at' => now()]);
                continue;
            }
            DB::table(self::TABLE)->insert([
                'business_id' => $businessId,
                'context_key' => $key,
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
        return DB::table(self::TABLE)->where('business_id', $businessId)
            ->orderBy('context_label')->orderByDesc('is_active')->orderByDesc('id')->get()
            ->map(function ($row) use ($businessId) {
                $row->is_used = $this->isUsed($row, $businessId);
                return $row;
            })->all();
    }

    public function preview(string $contextKey, int $businessId, mixed $paidOn = null): string
    {
        return $this->generate($contextKey, $businessId, $paidOn, false);
    }

    public function next(string $contextKey, int $businessId, mixed $paidOn = null): string
    {
        if (DB::transactionLevel() > 0) {
            return $this->generate($contextKey, $businessId, $paidOn, true);
        }
        return DB::transaction(fn () => $this->generate($contextKey, $businessId, $paidOn, true), 3);
    }

    private function generate(string $contextKey, int $businessId, mixed $paidOn, bool $consume): string
    {
        $meta = self::CONTEXTS[$contextKey] ?? null;
        if (! $meta) {
            throw new \InvalidArgumentException('Unsupported customer payment reference context: ' . $contextKey);
        }
        if ($businessId <= 0) {
            throw new \InvalidArgumentException('A valid business is required to generate the customer payment reference.');
        }

        $year = $this->year($paidOn);
        $profile = $this->active($contextKey, $businessId, $consume);
        $prefix = $profile ? (string) $profile->prefix : $meta['default_prefix'];
        $starting = $profile ? max(1, (int) $profile->starting_number) : 1;
        $length = $profile ? max(4, (int) $profile->number_length) : 4;

        if ($consume && Schema::hasTable('business')) {
            DB::table('business')->where('id', $businessId)->lockForUpdate()->first(['id']);
        }

        $max = $this->maxExistingNumber($businessId, $prefix, $year);
        $number = max($starting, $max + 1);
        $reference = $this->format($prefix, $year, $number, $length);

        if ($consume && $profile) {
            DB::table(self::TABLE)->where('id', $profile->id)->update([
                'used_count' => DB::raw('used_count + 1'),
                'first_used_at' => DB::raw('COALESCE(first_used_at, CURRENT_TIMESTAMP)'),
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $reference;
    }

    public function store(int $businessId, array $data, ?int $userId = null): int
    {
        $contextKey = (string) ($data['context_key'] ?? '');
        $meta = self::CONTEXTS[$contextKey] ?? null;
        if (! $meta) {
            throw ValidationException::withMessages(['context_key' => 'Please select a valid payment source.']);
        }
        $prefix = $this->normalisePrefix((string) ($data['prefix'] ?? ''));
        $starting = max(1, (int) ($data['starting_number'] ?? 1));
        $this->assertUniquePrefix($businessId, $prefix, null);

        return DB::transaction(function () use ($businessId, $contextKey, $meta, $prefix, $starting, $userId): int {
            DB::table(self::TABLE)->where('business_id', $businessId)->where('context_key', $contextKey)->where('is_active', 1)
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
                $replacement = DB::table(self::TABLE)->where('business_id', $businessId)->where('context_key', $row->context_key)->orderByDesc('id')->first();
                if ($replacement) {
                    DB::table(self::TABLE)->where('id', $replacement->id)->update(['is_active' => 1, 'updated_at' => now()]);
                }
            }
        }, 3);
        $this->ensureDefaults($businessId, $this->userId());
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
        $query = DB::table('transaction_payments')->where('business_id', $businessId)->where('payment_ref_no', 'like', $prefix . '%');
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        foreach ($query->pluck('payment_ref_no') as $reference) {
            if ($this->matchesPrefix((string) $reference, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function active(string $contextKey, int $businessId, bool $lock): ?object
    {
        if (! $this->available()) {
            return null;
        }
        $this->ensureDefaults($businessId, $this->userId());
        $query = DB::table(self::TABLE)->where('business_id', $businessId)->where('context_key', $contextKey)->where('is_active', 1)->orderByDesc('id');
        if ($lock && DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }
        return $query->first();
    }

    private function maxExistingNumber(int $businessId, string $prefix, int $year): int
    {
        if (! Schema::hasTable('transaction_payments') || ! Schema::hasColumn('transaction_payments', 'payment_ref_no')) {
            return 0;
        }
        $base = $prefix . $year . '-';
        $query = DB::table('transaction_payments')->where('business_id', $businessId)->where('payment_ref_no', 'like', $base . '%');
        if (Schema::hasColumn('transaction_payments', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        $row = $query->selectRaw("MAX(CAST(SUBSTRING_INDEX(payment_ref_no, '-', -1) AS UNSIGNED)) AS max_number")->first();
        return max(0, (int) ($row->max_number ?? 0));
    }

    private function row(int $businessId, int $id): object
    {
        $row = DB::table(self::TABLE)->where('business_id', $businessId)->where('id', $id)->first();
        if (! $row) {
            abort(404);
        }
        return $row;
    }

    private function assertUniquePrefix(int $businessId, string $prefix, ?int $ignoreId): void
    {
        $q = DB::table(self::TABLE)->where('business_id', $businessId)->whereRaw('UPPER(prefix) = ?', [$prefix]);
        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }
        if ($q->exists()) {
            throw ValidationException::withMessages(['prefix' => 'This prefix already exists in Customer Payment Reference settings.']);
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

    private function matchesPrefix(string $reference, string $prefix): bool
    {
        return (bool) preg_match('/^' . preg_quote($prefix, '/') . '\d{4}-\d+$/', trim($reference));
    }

    private function format(string $prefix, int $year, int $number, int $length): string
    {
        return $prefix . $year . '-' . str_pad((string) max(1, $number), max(4, $length), '0', STR_PAD_LEFT);
    }

    private function year(mixed $value): int
    {
        if ($value instanceof CarbonInterface) {
            return (int) $value->year;
        }
        try {
            return (int) Carbon::parse($value ?: now())->year;
        } catch (\Throwable $e) {
            return (int) Carbon::now()->year;
        }
    }

    private function userId(): ?int
    {
        return auth()->check() ? (int) auth()->id() : null;
    }
}
