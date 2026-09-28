<?php

namespace Modules\POS\Services;

use Illuminate\Support\Facades\DB;

class POSCashDrawerService extends POSBaseService
{
    public function movements()
    {
        if (!$this->tableExists('pos_cash_movements')) { return collect(); }
        $query = DB::table('pos_cash_movements as m')
            ->leftJoin('pos_register_sessions as s', 's.id', '=', 'm.register_session_id')
            ->leftJoin('pos_registers as r', 'r.id', '=', 's.register_id')
            ->select('m.*', 's.session_no', 'r.name as register_name');
        if ($this->businessId() && in_array('business_id', $this->columns('pos_cash_movements'))) {
            $query->where('m.business_id', $this->businessId());
        }
        return $query->orderByDesc('m.id')->paginate(25);
    }

    public function dashboard(): array
    {
        if (!$this->tableExists('pos_cash_movements')) {
            return ['cash_in' => 0, 'cash_out' => 0, 'refunds' => 0, 'net_cash' => 0];
        }
        $cashIn = $this->sumToday('cash_in');
        $cashOut = $this->sumToday('cash_out');
        $refunds = $this->sumToday('refund');
        return ['cash_in' => $cashIn, 'cash_out' => $cashOut, 'refunds' => $refunds, 'net_cash' => $cashIn - $cashOut - $refunds];
    }

    public function createMovement(string $type, array $input): int
    {
        if (!$this->tableExists('pos_cash_movements')) { return 0; }
        $data = $this->onlyExistingColumns('pos_cash_movements', [
            'business_id' => $this->businessId(),
            'business_location_id' => $input['business_location_id'] ?? $this->locationId(),
            'register_session_id' => $input['register_session_id'] ?? null,
            'movement_type' => $type,
            'amount' => $input['amount'] ?? 0,
            'transaction_date' => $input['transaction_date'] ?? $this->nowString(),
            'reference_no' => $input['reference_no'] ?? null,
            'note' => $input['note'] ?? null,
            'created_by' => $this->userId(),
            'approved_by' => null,
            'approval_status' => $type === 'cash_out' ? 'pending' : 'approved',
            'created_at' => $this->nowString(),
            'updated_at' => $this->nowString(),
        ]);
        return (int) DB::table('pos_cash_movements')->insertGetId($data);
    }

    public function createCount(array $input): int
    {
        if (!$this->tableExists('pos_cash_drawer_counts')) { return 0; }
        $expected = (float) ($input['expected_amount'] ?? 0);
        $actual = (float) ($input['actual_amount'] ?? 0);
        $data = $this->onlyExistingColumns('pos_cash_drawer_counts', [
            'business_id' => $this->businessId(),
            'business_location_id' => $input['business_location_id'] ?? $this->locationId(),
            'register_session_id' => $input['register_session_id'] ?? null,
            'counted_at' => $input['counted_at'] ?? $this->nowString(),
            'expected_amount' => $expected,
            'actual_amount' => $actual,
            'variance_amount' => $actual - $expected,
            'note' => $input['note'] ?? null,
            'created_by' => $this->userId(),
            'created_at' => $this->nowString(),
            'updated_at' => $this->nowString(),
        ]);
        return (int) DB::table('pos_cash_drawer_counts')->insertGetId($data);
    }

    public function openSessions()
    {
        if (!$this->tableExists('pos_register_sessions')) { return collect(); }
        return DB::table('pos_register_sessions as s')
            ->leftJoin('pos_registers as r', 'r.id', '=', 's.register_id')
            ->where('s.status', 'open')
            ->select('s.id', 's.session_no', 'r.name as register_name', 's.opening_amount')
            ->orderByDesc('s.id')
            ->get();
    }

    protected function sumToday(string $type): float
    {
        return (float) DB::table('pos_cash_movements')
            ->where('movement_type', $type)
            ->whereDate('transaction_date', now()->toDateString())
            ->sum('amount');
    }
}
