<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Purchase-module adapter for supplier payment reference numbers.
 *
 * Some installations provide App\Services\SupplierPaymentReferenceService,
 * while older tenant deployments (including standalone module deployments)
 * do not.  The Purchase module must therefore not require that core class in
 * order to open Add Purchase / Add Payment screens.
 *
 * When the global service exists we delegate to it so current installations
 * keep their exact existing numbering behaviour.  Otherwise the module uses
 * the same tenant tables directly and falls back safely to APEP/LPEP numbering.
 */
class SupplierPaymentReferenceService
{
    private bool $delegateResolved = false;

    private ?object $delegate = null;

    public function preview(string $defaultPrefix, int $businessId, mixed $when = null): string
    {
        $delegate = $this->delegate();
        if ($delegate && method_exists($delegate, 'preview')) {
            try {
                return (string) $delegate->preview($defaultPrefix, $businessId, $when);
            } catch (\Throwable $e) {
                // Standalone fallback below. A missing/incompatible global service
                // must never prevent the Purchase form from opening.
            }
        }

        return $this->fallbackReference($defaultPrefix, $businessId, $when, false);
    }

    public function next(string $defaultPrefix, int $businessId, mixed $when = null): string
    {
        $delegate = $this->delegate();
        if ($delegate && method_exists($delegate, 'next')) {
            try {
                return (string) $delegate->next($defaultPrefix, $businessId, $when);
            } catch (\Throwable $e) {
                // Use module-owned compatibility logic when the application-level
                // implementation is not usable on this tenant.
            }
        }

        return DB::transaction(function () use ($defaultPrefix, $businessId, $when): string {
            return $this->fallbackReference($defaultPrefix, $businessId, $when, true);
        }, 3);
    }

    public function isSystemReference(?string $reference): bool
    {
        $reference = trim((string) $reference);
        if ($reference === '') {
            return false;
        }

        $delegate = $this->delegate();
        if ($delegate && method_exists($delegate, 'isSystemReference')) {
            try {
                return (bool) $delegate->isSystemReference($reference);
            } catch (\Throwable $e) {
                // Continue with the standalone checks below.
            }
        }

        // Existing payment_ref_no values are safe to preserve during an edit.
        // PurchaseEntryPaymentService additionally checks that the value belongs
        // to the allowed references of the purchase being edited.
        if (Schema::hasTable('transaction_payments')
            && Schema::hasColumn('transaction_payments', 'payment_ref_no')) {
            try {
                if (DB::table('transaction_payments')->where('payment_ref_no', $reference)->exists()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Fall through to format detection.
            }
        }

        // Standard supplier payment identity: PREFIX + YYYY + '-' + sequence.
        // Prefixes can be customised, so keep the prefix portion permissive but
        // require the year/sequence structure.
        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,39}\d{4}-\d+$/', $reference);
    }

    private function delegate(): ?object
    {
        if ($this->delegateResolved) {
            return $this->delegate;
        }

        $this->delegateResolved = true;
        $class = 'App\\Services\\SupplierPaymentReferenceService';

        if (! class_exists($class)) {
            return null;
        }

        try {
            $resolved = app($class);
            // When PurchaseServiceProvider created a compatibility class_alias,
            // resolving the legacy App\Services name returns this same module
            // implementation.  Never treat that alias as an external delegate,
            // otherwise preview()/next() would recurse indefinitely.
            if ($resolved !== $this && ! ($resolved instanceof self)) {
                $this->delegate = $resolved;
            }
        } catch (\Throwable $e) {
            $this->delegate = null;
        }

        return $this->delegate;
    }

    private function fallbackReference(
        string $defaultPrefix,
        int $businessId,
        mixed $when,
        bool $consume
    ): string {
        $defaultPrefix = $this->normalisePrefix($defaultPrefix);
        $businessId = max(0, $businessId);
        $year = $this->year($when);

        $config = $this->configuration($defaultPrefix, $businessId, $consume);
        $prefix = $this->normalisePrefix((string) ($config['prefix'] ?? $defaultPrefix));
        $startingNumber = max(1, (int) ($config['starting_number'] ?? 1));
        $numberLength = max(1, min(12, (int) ($config['number_length'] ?? 4)));
        $usedCount = max(0, (int) ($config['used_count'] ?? 0));

        $base = $prefix . $year . '-';
        $nextNumber = max(
            $startingNumber + $usedCount,
            $this->highestExistingNumber($base, $businessId) + 1
        );

        // Do not ever return an already-used reference, even if a legacy
        // sequence counter is stale.
        while ($this->referenceExists($base . str_pad((string) $nextNumber, $numberLength, '0', STR_PAD_LEFT), $businessId)) {
            $nextNumber++;
        }

        $reference = $base . str_pad((string) $nextNumber, $numberLength, '0', STR_PAD_LEFT);

        if ($consume && ! empty($config['id'])) {
            $this->markConfigurationUsed($config, $nextNumber, $startingNumber);
        }

        return $reference;
    }

    /**
     * @return array<string, mixed>
     */
    private function configuration(string $defaultPrefix, int $businessId, bool $lock): array
    {
        if ($businessId <= 0
            || ! Schema::hasTable('supplier_payment_reference_prefixes')
            || ! Schema::hasColumn('supplier_payment_reference_prefixes', 'business_id')
            || ! Schema::hasColumn('supplier_payment_reference_prefixes', 'prefix')) {
            return [];
        }

        try {
            $query = DB::table('supplier_payment_reference_prefixes')
                ->where('business_id', $businessId);

            if (Schema::hasColumn('supplier_payment_reference_prefixes', 'is_active')) {
                $query->where(function ($active): void {
                    $active->whereNull('is_active')->orWhere('is_active', 1);
                });
            }

            // Prefer the configured row whose current prefix still equals the
            // module default. This is the least ambiguous and most common case.
            $exact = clone $query;
            $exact->where('prefix', $defaultPrefix);
            if ($lock) {
                $exact->lockForUpdate();
            }
            $row = $exact->orderBy('id')->first();
            if ($row) {
                return (array) $row;
            }

            // If the user customised the prefix, identify the setting by its
            // context_key.  Older deployments used slightly different context
            // labels, therefore rank all Purchase-related rows instead of
            // assuming one schema version.
            if (Schema::hasColumn('supplier_payment_reference_prefixes', 'context_key')) {
                $candidates = clone $query;
                if ($lock) {
                    $candidates->lockForUpdate();
                }
                $rows = $candidates->orderBy('id')->get();
                $best = null;
                $bestScore = 0;
                foreach ($rows as $candidate) {
                    $score = $this->contextScore($defaultPrefix, (string) ($candidate->context_key ?? ''));
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = $candidate;
                    }
                }
                if ($best && $bestScore >= 8) {
                    return (array) $best;
                }
            }
        } catch (\Throwable $e) {
            // A partially upgraded older tenant must still be able to use the
            // Purchase screen with the module's safe default numbering.
        }

        return [];
    }

    private function contextScore(string $defaultPrefix, string $contextKey): int
    {
        $key = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $contextKey), '_'));
        if ($key === '') {
            return 0;
        }

        $prefix = strtoupper($defaultPrefix);
        $score = str_contains($key, 'purchase') ? 5 : 0;
        $score += str_contains($key, 'payment') ? 3 : 0;

        if ($prefix === 'APEP') {
            $exact = [
                'add_purchase_entry_payment',
                'purchase_entry_payment',
                'add_purchase_payment',
                'purchase_entry',
                'add_purchase_entry',
            ];
            if (in_array($key, $exact, true)) {
                $score += 20;
            }
            $score += str_contains($key, 'entry') ? 4 : 0;
            $score += str_contains($key, 'add') ? 3 : 0;
            $score -= str_contains($key, 'list') ? 6 : 0;
        } elseif ($prefix === 'LPEP') {
            $exact = [
                'list_purchase_entry_payment',
                'list_purchase_payment',
                'purchase_add_payment',
                'purchase_entry_add_payment',
                'list_purchase_entry_add_payment',
            ];
            if (in_array($key, $exact, true)) {
                $score += 20;
            }
            $score += str_contains($key, 'list') ? 5 : 0;
            $score += str_contains($key, 'add_payment') ? 5 : 0;
            $score += str_contains($key, 'due') ? 2 : 0;
        }

        return max(0, $score);
    }

    private function highestExistingNumber(string $base, int $businessId): int
    {
        if (! Schema::hasTable('transaction_payments')
            || ! Schema::hasColumn('transaction_payments', 'payment_ref_no')) {
            return 0;
        }

        try {
            $query = DB::table('transaction_payments')
                ->where('payment_ref_no', 'like', $base . '%');
            if ($businessId > 0 && Schema::hasColumn('transaction_payments', 'business_id')) {
                $query->where('business_id', $businessId);
            }

            $max = 0;
            foreach ($query->pluck('payment_ref_no') as $reference) {
                $reference = (string) $reference;
                if (! str_starts_with($reference, $base)) {
                    continue;
                }
                $suffix = substr($reference, strlen($base));
                if ($suffix !== '' && ctype_digit($suffix)) {
                    $max = max($max, (int) $suffix);
                }
            }

            return $max;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function referenceExists(string $reference, int $businessId): bool
    {
        if (! Schema::hasTable('transaction_payments')
            || ! Schema::hasColumn('transaction_payments', 'payment_ref_no')) {
            return false;
        }

        try {
            $query = DB::table('transaction_payments')->where('payment_ref_no', $reference);
            if ($businessId > 0 && Schema::hasColumn('transaction_payments', 'business_id')) {
                $query->where('business_id', $businessId);
            }

            return $query->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function markConfigurationUsed(array $config, int $numberUsed, int $startingNumber): void
    {
        if (empty($config['id']) || ! Schema::hasTable('supplier_payment_reference_prefixes')) {
            return;
        }

        try {
            $updates = [];
            if (Schema::hasColumn('supplier_payment_reference_prefixes', 'used_count')) {
                $requiredCount = max(1, $numberUsed - $startingNumber + 1);
                $updates['used_count'] = max((int) ($config['used_count'] ?? 0) + 1, $requiredCount);
            }
            if (Schema::hasColumn('supplier_payment_reference_prefixes', 'first_used_at')
                && empty($config['first_used_at'])) {
                $updates['first_used_at'] = now();
            }
            if (Schema::hasColumn('supplier_payment_reference_prefixes', 'last_used_at')) {
                $updates['last_used_at'] = now();
            }
            if (Schema::hasColumn('supplier_payment_reference_prefixes', 'updated_at')) {
                $updates['updated_at'] = now();
            }

            if ($updates !== []) {
                DB::table('supplier_payment_reference_prefixes')
                    ->where('id', (int) $config['id'])
                    ->update($updates);
            }
        } catch (\Throwable $e) {
            // Reference generation must remain available even when an optional
            // usage-statistics column/table is from an older schema version.
        }
    }

    private function normalisePrefix(string $prefix): string
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return 'APEP';
        }

        // Keep configured punctuation but remove whitespace/control characters
        // so generated references stay stable and URL/report friendly.
        $prefix = (string) preg_replace('/\s+/', '', $prefix);

        return substr($prefix, 0, 20);
    }

    private function year(mixed $when): string
    {
        try {
            if ($when instanceof \DateTimeInterface) {
                return $when->format('Y');
            }

            if ($when !== null && trim((string) $when) !== '') {
                return Carbon::parse($when)->format('Y');
            }
        } catch (\Throwable $e) {
            // Use the tenant/app current year below.
        }

        return now()->format('Y');
    }
}
