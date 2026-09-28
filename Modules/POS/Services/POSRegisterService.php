<?php

namespace Modules\POS\Services;

use Illuminate\Support\Facades\DB;

class POSRegisterService extends POSBaseService
{
    public function list()
    {
        if (!$this->tableExists('pos_registers')) { return collect(); }
        $query = DB::table('pos_registers');
        if ($this->businessId() && in_array('business_id', $this->columns('pos_registers'))) {
            $query->where('business_id', $this->businessId());
        }
        return $query->orderByDesc('id')->paginate(25);
    }

    public function find(int $id): ?object
    {
        if (!$this->tableExists('pos_registers')) { return null; }
        return DB::table('pos_registers')->where('id', $id)->first();
    }

    public function create(array $input): int
    {
        if (!$this->tableExists('pos_registers')) { return 0; }
        $data = $this->onlyExistingColumns('pos_registers', [
            'business_id' => $this->businessId(),
            'business_location_id' => $input['business_location_id'] ?? $this->locationId(),
            'name' => $input['name'] ?? null,
            'code' => $input['code'] ?? null,
            'description' => $input['description'] ?? null,
            'opening_balance' => $input['opening_balance'] ?? 0,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'note' => $input['note'] ?? null,
            'created_by' => $this->userId(),
            'created_at' => $this->nowString(),
            'updated_at' => $this->nowString(),
        ]);
        return (int) DB::table('pos_registers')->insertGetId($data);
    }

    public function update(int $id, array $input): void
    {
        if (!$this->tableExists('pos_registers')) { return; }
        $data = $this->onlyExistingColumns('pos_registers', [
            'business_location_id' => $input['business_location_id'] ?? $this->locationId(),
            'name' => $input['name'] ?? null,
            'code' => $input['code'] ?? null,
            'description' => $input['description'] ?? null,
            'opening_balance' => $input['opening_balance'] ?? 0,
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'note' => $input['note'] ?? null,
            'updated_at' => $this->nowString(),
        ]);
        DB::table('pos_registers')->where('id', $id)->update($data);
    }

    public function activeRegisters()
    {
        if (!$this->tableExists('pos_registers')) { return collect(); }
        $query = DB::table('pos_registers');
        if (in_array('is_active', $this->columns('pos_registers'))) { $query->where('is_active', 1); }
        if ($this->businessId() && in_array('business_id', $this->columns('pos_registers'))) { $query->where('business_id', $this->businessId()); }
        return $query->orderBy('name')->get();
    }
}
