<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CashierControlService
{
    public function dashboard(): array
    {
        $shifts = $this->rows('hm_cashier_shifts');
        $drops = $this->rows('hm_cashier_safe_drops');
        $counts = $this->rows('hm_cashier_cash_counts');
        $variance = $this->rows('hm_cashier_variances');
        $open = array_values(array_filter($shifts, fn($r) => ($r->status ?? '') === 'open'));
        $closed = array_values(array_filter($shifts, fn($r) => ($r->status ?? '') === 'closed'));
        $expected = array_sum(array_map(fn($r) => (float)($r->expected_cash ?? 0), $open));
        $declared = array_sum(array_map(fn($r) => (float)($r->declared_cash ?? 0), $closed));
        $varianceAmount = array_sum(array_map(fn($r) => (float)($r->variance_amount ?? 0), $variance));

        return [
            'shifts' => $shifts,
            'drops' => $drops,
            'counts' => $counts,
            'variances' => $variance,
            'open_shift_count' => count($open),
            'closed_shift_count' => count($closed),
            'expected_cash' => $expected,
            'declared_cash' => $declared,
            'variance_amount' => $varianceAmount,
            'notes' => [
                'Cashier Control is scoped to current tenant database, business_id and business_location_id.',
                'Safe drops reduce cashier drawer exposure and are retained for audit verification.',
                'Shift close records denomination count, declared cash and shortage/excess variance.',
            ],
        ];
    }

    public function openShift(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_cashier_shifts')) return;
        DB::table('hm_cashier_shifts')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'shift_no' => $this->nextNumber('hm_cashier_shifts', 'shift_no', 'CSH'),
            'cashier_user_id' => $data['cashier_user_id'] ?? $userId,
            'counter_name' => $data['counter_name'] ?? 'Front Desk',
            'opening_float' => (float)($data['opening_float'] ?? 0),
            'expected_cash' => (float)($data['opening_float'] ?? 0),
            'declared_cash' => 0,
            'variance_amount' => 0,
            'opened_at' => $data['opened_at'] ?? now(),
            'closed_at' => null,
            'status' => 'open',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function safeDrop(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_cashier_safe_drops') || !Schema::hasTable('hm_cashier_shifts')) return;
        $shift = $this->shift((int)$data['shift_id']);
        if (!$shift || $shift->status !== 'open') return;
        $amount = (float)($data['drop_amount'] ?? 0);
        DB::transaction(function () use ($data, $userId, $shift, $amount) {
            DB::table('hm_cashier_safe_drops')->insert([
                'business_id' => $this->businessId(),
                'business_location_id' => $this->locationId(),
                'shift_id' => $shift->id,
                'drop_no' => $this->nextNumber('hm_cashier_safe_drops', 'drop_no', 'DROP'),
                'drop_datetime' => $data['drop_datetime'] ?? now(),
                'drop_amount' => $amount,
                'received_by' => $data['received_by'] ?? null,
                'safe_bag_no' => $data['safe_bag_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('hm_cashier_shifts')->where('id', $shift->id)->update([
                'expected_cash' => max(0, (float)$shift->expected_cash - $amount),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
        });
    }

    public function closeShift(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_cashier_shifts')) return;
        $shift = $this->shift((int)$data['shift_id']);
        if (!$shift || $shift->status !== 'open') return;
        $declared = (float)($data['declared_cash'] ?? 0);
        $variance = $declared - (float)$shift->expected_cash;
        DB::transaction(function () use ($data, $userId, $shift, $declared, $variance) {
            DB::table('hm_cashier_shifts')->where('id', $shift->id)->update([
                'declared_cash' => $declared,
                'variance_amount' => $variance,
                'closed_at' => $data['closed_at'] ?? now(),
                'status' => 'closed',
                'remarks' => $data['remarks'] ?? $shift->remarks,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            if (Schema::hasTable('hm_cashier_variances') && abs($variance) > 0.0001) {
                DB::table('hm_cashier_variances')->insert([
                    'business_id' => $this->businessId(),
                    'business_location_id' => $this->locationId(),
                    'shift_id' => $shift->id,
                    'variance_no' => $this->nextNumber('hm_cashier_variances', 'variance_no', 'VAR'),
                    'variance_type' => $variance > 0 ? 'excess' : 'shortage',
                    'variance_amount' => abs($variance),
                    'reason' => $data['variance_reason'] ?? null,
                    'status' => 'pending_review',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function cashCount(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_cashier_cash_counts')) return;
        $qty = (int)($data['quantity'] ?? 0);
        $denomination = (float)($data['denomination'] ?? 0);
        DB::table('hm_cashier_cash_counts')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'shift_id' => $data['shift_id'],
            'denomination' => $denomination,
            'quantity' => $qty,
            'line_total' => $denomination * $qty,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function reviewVariance(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_cashier_variances')) return;
        DB::table('hm_cashier_variances')
            ->where('id', $id)
            ->where('business_id', $this->businessId())
            ->update([
                'status' => $data['status'] ?? 'reviewed',
                'review_note' => $data['review_note'] ?? null,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
    }

    private function shift(int $id)
    {
        return DB::table('hm_cashier_shifts')->where('id', $id)->where('business_id', $this->businessId())->first();
    }

    private function rows(string $table): array
    {
        if (!Schema::hasTable($table)) return [];
        try {
            return DB::table($table)->where('business_id', $this->businessId())->orderByDesc('id')->limit(100)->get()->all();
        } catch (Throwable $e) { return []; }
    }

    private function nextNumber(string $table, string $column, string $prefix): string
    {
        $last = Schema::hasTable($table) ? DB::table($table)->where('business_id', $this->businessId())->orderByDesc('id')->value($column) : null;
        $n = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) $n = ((int)$m[1]) + 1;
        return $prefix . '-' . date('ym') . '-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
    }

    private function businessId(): int { return (int)(session('business.id') ?? session('business_id') ?? 1); }
    private function locationId(): ?int { return session('business_location_id') ?? session('location_id') ?? null; }
}
