<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RevenueManagementService
{
    public function dashboard(array $filters = []): array
    {
        $seasons = $this->tableRows('hm_revenue_seasons');
        $yieldRules = $this->tableRows('hm_yield_rules');
        $contracts = $this->tableRows('hm_corporate_contracts');
        $agentContracts = $this->tableRows('hm_travel_agent_contracts');
        $groupBlocks = $this->tableRows('hm_group_room_blocks');
        $splitFolios = $this->tableRows('hm_split_folio_rules');

        return [
            'seasons' => $seasons,
            'yield_rules' => $yieldRules,
            'contracts' => $contracts,
            'agent_contracts' => $agentContracts,
            'group_blocks' => $groupBlocks,
            'split_folios' => $splitFolios,
            'active_seasons' => count(array_filter($seasons, fn($r) => (int)($r->is_active ?? 0) === 1)),
            'active_yield_rules' => count(array_filter($yieldRules, fn($r) => (int)($r->is_active ?? 0) === 1)),
            'credit_exposure' => array_sum(array_map(fn($r) => (float)($r->current_balance ?? 0), $contracts)),
            'blocked_rooms' => array_sum(array_map(fn($r) => (int)($r->blocked_rooms ?? 0), $groupBlocks)),
            'notes' => [
                'All records are scoped by tenant database, business_id and business_location_id where available.',
                'Seasonal pricing and yield rules are stored separately so rate calculations can remain auditable.',
                'Corporate and travel agent contracts support credit control without duplicating the Customers/Suppliers modules.',
            ],
        ];
    }

    public function saveSeason(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_revenue_seasons')) return;
        DB::table('hm_revenue_seasons')->updateOrInsert([
            'business_id' => $this->businessId(),
            'season_code' => strtoupper($data['season_code']),
        ], [
            'business_location_id' => $this->locationId(),
            'season_name' => $data['season_name'],
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'rate_adjustment_type' => $data['rate_adjustment_type'],
            'rate_adjustment_value' => (float)$data['rate_adjustment_value'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveYieldRule(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_yield_rules')) return;
        DB::table('hm_yield_rules')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'rule_name' => $data['rule_name'],
            'occupancy_from' => (float)$data['occupancy_from'],
            'occupancy_to' => (float)$data['occupancy_to'],
            'adjustment_type' => $data['adjustment_type'],
            'adjustment_value' => (float)$data['adjustment_value'],
            'priority' => (int)($data['priority'] ?? 1),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveCorporateContract(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_corporate_contracts')) return;
        DB::table('hm_corporate_contracts')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'contract_no' => $this->nextNumber('hm_corporate_contracts', 'contract_no', 'CORP'),
            'company_name' => $data['company_name'],
            'contact_person' => $data['contact_person'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'email' => $data['email'] ?? null,
            'contract_from' => $data['contract_from'] ?? null,
            'contract_to' => $data['contract_to'] ?? null,
            'rate_type' => $data['rate_type'] ?? 'contracted',
            'discount_percent' => (float)($data['discount_percent'] ?? 0),
            'credit_limit' => (float)($data['credit_limit'] ?? 0),
            'current_balance' => (float)($data['current_balance'] ?? 0),
            'direct_billing_allowed' => !empty($data['direct_billing_allowed']) ? 1 : 0,
            'status' => $data['status'] ?? 'active',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveAgentContract(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_travel_agent_contracts')) return;
        DB::table('hm_travel_agent_contracts')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'agent_name' => $data['agent_name'],
            'agent_code' => strtoupper($data['agent_code']),
            'commission_type' => $data['commission_type'] ?? 'percent',
            'commission_value' => (float)($data['commission_value'] ?? 0),
            'contract_from' => $data['contract_from'] ?? null,
            'contract_to' => $data['contract_to'] ?? null,
            'credit_limit' => (float)($data['credit_limit'] ?? 0),
            'status' => $data['status'] ?? 'active',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveGroupBlock(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_group_room_blocks')) return;
        DB::table('hm_group_room_blocks')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'block_no' => $this->nextNumber('hm_group_room_blocks', 'block_no', 'GRP'),
            'group_name' => $data['group_name'],
            'arrival_date' => $data['arrival_date'],
            'departure_date' => $data['departure_date'],
            'blocked_rooms' => (int)$data['blocked_rooms'],
            'released_rooms' => 0,
            'rate_amount' => (float)($data['rate_amount'] ?? 0),
            'cutoff_date' => $data['cutoff_date'] ?? null,
            'status' => $data['status'] ?? 'blocked',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveSplitFolioRule(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_split_folio_rules')) return;
        DB::table('hm_split_folio_rules')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'rule_name' => $data['rule_name'],
            'payer_type' => $data['payer_type'],
            'charge_category' => $data['charge_category'],
            'split_type' => $data['split_type'],
            'split_value' => (float)$data['split_value'],
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function releaseGroupRooms(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_group_room_blocks')) return;
        $block = DB::table('hm_group_room_blocks')->where('id', $id)->where('business_id', $this->businessId())->first();
        if (!$block) return;
        $release = min((int)$data['release_rooms'], max(0, (int)$block->blocked_rooms - (int)$block->released_rooms));
        DB::table('hm_group_room_blocks')->where('id', $id)->where('business_id', $this->businessId())->update([
            'released_rooms' => (int)$block->released_rooms + $release,
            'status' => ((int)$block->released_rooms + $release) >= (int)$block->blocked_rooms ? 'released' : ($data['status'] ?? $block->status),
            'updated_by' => $userId,
            'updated_at' => now(),
        ]);
    }

    protected function tableRows(string $table): array
    {
        if (!Schema::hasTable($table)) return [];
        try {
            return DB::table($table)->where('business_id', $this->businessId())->orderByDesc('id')->limit(200)->get()->toArray();
        } catch (Throwable $e) { return []; }
    }

    protected function nextNumber(string $table, string $column, string $prefix): string
    {
        $prefix = $prefix . '-' . date('ymd') . '-';
        $last = Schema::hasTable($table) ? DB::table($table)->where('business_id', $this->businessId())->where($column, 'like', $prefix.'%')->orderByDesc('id')->value($column) : null;
        $next = $last ? ((int)substr($last, -4)) + 1 : 1;
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    protected function businessId(): ?int { return session('business.id') ?? session('business_id') ?? null; }
    protected function locationId(): ?int { return session('business_location_id') ?? session('business.default_location_id') ?? null; }
}
