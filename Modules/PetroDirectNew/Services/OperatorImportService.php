<?php

namespace Modules\PetroDirectNew\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroDirectNew\Support\BusinessContext;

class OperatorImportService
{
    public function __construct(private BusinessContext $context)
    {
    }

    /**
     * Perform a lightweight freshness check. A full synchronization is executed
     * only when the module has no rows for the requested scope or the source has
     * changed since the last successful import.
     */
    public function ensureFresh(?int $locationId = null): array
    {
        if (!$this->available()) {
            return $this->result(false, 0, 0, true);
        }

        $businessId = $this->context->requireBusiness();
        $this->assertLocation($locationId);

        $target = DB::table('pdirectnew_operators')->where('business_id', $businessId);
        $source = DB::table('pump_operators')->where('business_id', $businessId);
        $sourceColumns = array_flip(Schema::getColumnListing('pump_operators'));

        $this->applyLocationScope($target, $locationId, true);
        $this->applyLocationScope($source, $locationId, isset($sourceColumns['location_id']));

        if (!$target->exists()) {
            return $this->sync($locationId);
        }

        if (isset($sourceColumns['updated_at']) && Schema::hasColumn('pdirectnew_operators', 'source_updated_at')) {
            $sourceMax = $source->max('updated_at');
            $targetMax = $target->max('source_updated_at');

            if ($sourceMax && (!$targetMax || Carbon::parse($sourceMax)->gt(Carbon::parse($targetMax)))) {
                return $this->sync($locationId);
            }
        }

        return $this->result(true, 0, 0, true, true);
    }

    /**
     * Synchronize current-system Pump Operators using two reads and batched
     * writes. This avoids the old one-select/one-update-per-operator pattern.
     */
    public function sync(?int $locationId = null): array
    {
        if (!$this->available()) {
            return $this->result(false, 0, 0, true);
        }

        $businessId = $this->context->requireBusiness();
        $this->assertLocation($locationId);

        $sourceColumns = array_flip(Schema::getColumnListing('pump_operators'));
        if (!isset($sourceColumns['id'], $sourceColumns['business_id'], $sourceColumns['name'])) {
            return $this->result(false, 0, 0, true);
        }

        $sourceRows = $this->sourceQuery($businessId, $locationId, $sourceColumns)->get();
        if ($sourceRows->isEmpty()) {
            return $this->result(true, 0, 0, true);
        }

        $existingRows = DB::table('pdirectnew_operators')
            ->where('business_id', $businessId)
            ->get();

        $bySource = [];
        $byNumber = [];
        foreach ($existingRows as $existing) {
            if (!empty($existing->source_operator_id)) {
                $bySource[(int) $existing->source_operator_id] = $existing;
            }
            $byNumber[strtolower(trim((string) $existing->operator_no))] = $existing;
        }

        $now = now();
        $updates = [];
        $inserts = [];

        foreach ($sourceRows as $source) {
            $sourceId = (int) $source->id;
            $operatorNo = trim((string) ($source->operator_no ?? ''));
            if ($operatorNo === '') {
                $operatorNo = 'PD-' . $sourceId;
            }

            $existing = $bySource[$sourceId] ?? $byNumber[strtolower($operatorNo)] ?? null;
            $metadata = $existing ? $this->decodeMetadata($existing->metadata) : [];
            $metadata = array_merge($metadata, [
                'shared_source' => 'pump_operators',
                'shared_source_id' => $sourceId,
                'assigned_pump_id' => isset($source->assigned_pump_id) ? (int) $source->assigned_pump_id : null,
                'synced_at' => $now->toIso8601String(),
            ]);

            $active = $this->sourceIsActive($source);
            $row = [
                'business_id' => $businessId,
                'location_id' => $this->nullableInt($source->location_id ?? null),
                'user_id' => $this->nullableInt($source->user_id ?? null),
                'source_operator_id' => $sourceId,
                'operator_no' => $operatorNo,
                'name' => trim((string) $source->name) ?: ('Pump Operator ' . $sourceId),
                'address' => $this->nullableString($source->address ?? null),
                'mobile' => $this->nullableString($source->mobile ?? null),
                'landline' => $this->nullableString($source->landline ?? null),
                'dob' => $this->nullableDate($source->dob ?? null),
                'nic' => $this->nullableString($source->nic ?? $source->cnic ?? null),
                'email' => $this->nullableString($source->email ?? $source->linked_email ?? null),
                'username' => $this->nullableString($source->username ?? $source->linked_username ?? null),
                'opening_balance' => $this->number($source->opening_balance ?? 0),
                'commission_type' => $this->commissionType($source->commission_type ?? null),
                'commission_value' => $this->number($source->commission_value ?? $source->commission_ap ?? 0),
                'short_amount' => $this->number($source->short_amount ?? 0),
                'excess_amount' => $this->number($source->excess_amount ?? 0),
                'transaction_date' => $this->nullableDate($source->transaction_date ?? null),
                'is_default' => $this->flag($source->is_default ?? false),
                'can_fullscreen' => $this->flag($source->can_fullscreen ?? false),
                'hide_in_direct_settlement_if_pending_shifts' => $this->flag($source->hide_in_direct_settlement_if_pending_shifts ?? false),
                'status' => $active ? 'active' : 'inactive',
                'is_active' => $active ? 1 : 0,
                'source_updated_at' => $source->updated_at ?? $now,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ];

            // Once an operator is edited inside Petro Direct-New, keep the local
            // profile fields stable. Source identity and freshness markers still
            // remain linked for audit and automatic discovery.
            if ($existing && !empty($metadata['local_override'])) {
                foreach ([
                    'location_id', 'user_id', 'operator_no', 'name', 'address', 'mobile',
                    'landline', 'dob', 'nic', 'email', 'username', 'opening_balance',
                    'commission_type', 'commission_value', 'short_amount', 'excess_amount',
                    'transaction_date', 'is_default', 'can_fullscreen',
                    'hide_in_direct_settlement_if_pending_shifts', 'status', 'is_active',
                ] as $field) {
                    if (property_exists($existing, $field)) {
                        $row[$field] = $existing->{$field};
                    }
                }
            }

            if ($existing) {
                $row['id'] = (int) $existing->id;
                $updates[] = $row;
            } else {
                $row['can_login'] = 0;
                $row['created_at'] = $now;
                $inserts[] = $row;
            }
        }

        DB::transaction(function () use ($updates, $inserts) {
            $updateColumns = [
                'location_id', 'user_id', 'source_operator_id', 'operator_no', 'name', 'address',
                'mobile', 'landline', 'dob', 'nic', 'email', 'username', 'opening_balance',
                'commission_type', 'commission_value', 'short_amount', 'excess_amount',
                'transaction_date', 'is_default', 'can_fullscreen',
                'hide_in_direct_settlement_if_pending_shifts', 'status', 'is_active',
                'source_updated_at', 'metadata', 'updated_at',
            ];

            foreach (array_chunk($updates, 500) as $chunk) {
                DB::table('pdirectnew_operators')->upsert($chunk, ['id'], $updateColumns);
            }

            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('pdirectnew_operators')->insertOrIgnore($chunk);
            }
        });

        return $this->result(true, count($inserts), count($updates), true);
    }

    private function sourceQuery(int $businessId, ?int $locationId, array $columns): Builder
    {
        $query = DB::table('pump_operators as po')->where('po.business_id', $businessId);
        $select = ['po.id', 'po.name'];

        foreach ([
            'location_id', 'user_id', 'operator_no', 'address', 'mobile', 'landline', 'dob',
            'cnic', 'nic', 'email', 'username', 'opening_balance', 'commission_type',
            'commission_ap', 'commission_value', 'short_amount', 'excess_amount',
            'transaction_date', 'is_default', 'can_fullscreen',
            'hide_in_direct_settlement_if_pending_shifts', 'assigned_pump_id',
            'active', 'status', 'updated_at',
        ] as $column) {
            if (isset($columns[$column])) {
                $select[] = 'po.' . $column;
            }
        }

        if (isset($columns['user_id']) && Schema::hasTable('users')) {
            $query->leftJoin('users as u', 'u.id', '=', 'po.user_id');
            if (Schema::hasColumn('users', 'username')) {
                $select[] = DB::raw('u.username as linked_username');
            }
            if (Schema::hasColumn('users', 'email')) {
                $select[] = DB::raw('u.email as linked_email');
            }
        }

        $query->select($select)->orderBy('po.id');
        $this->applyLocationScope($query, $locationId, isset($columns['location_id']), 'po.location_id');

        return $query;
    }

    private function applyLocationScope(Builder $query, ?int $locationId, bool $hasLocationColumn, string $column = 'location_id'): void
    {
        if (!$hasLocationColumn) {
            return;
        }

        if ($locationId) {
            $query->where(function (Builder $scope) use ($column, $locationId) {
                $scope->where($column, $locationId)->orWhereNull($column);
            });
            return;
        }

        $permitted = $this->context->permittedLocationIds();
        if ($permitted !== []) {
            $query->where(function (Builder $scope) use ($column, $permitted) {
                $scope->whereIn($column, $permitted)->orWhereNull($column);
            });
        }
    }

    private function assertLocation(?int $locationId): void
    {
        if ($locationId) {
            abort_unless($this->context->locationAllowed($locationId), 403);
        }
    }

    private function available(): bool
    {
        return Schema::hasTable('pump_operators') && Schema::hasTable('pdirectnew_operators');
    }

    private function sourceIsActive(object $source): bool
    {
        if (isset($source->active)) {
            return (bool) $source->active;
        }

        if (!isset($source->status)) {
            return true;
        }

        $status = strtolower(trim((string) $source->status));
        return !in_array($status, ['0', 'inactive', 'disabled', 'deleted'], true);
    }

    private function commissionType(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['fixed', 'percentage'], true) ? $value : 'none';
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function flag(mixed $value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    private function decodeMetadata(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function result(bool $available, int $created, int $updated, bool $success, bool $skipped = false): array
    {
        return compact('available', 'created', 'updated', 'success', 'skipped');
    }
}
