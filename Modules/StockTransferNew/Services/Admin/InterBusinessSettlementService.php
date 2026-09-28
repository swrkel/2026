<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class InterBusinessSettlementService
{
    public function summary(array $filters = []): array
    {
        $query = $this->settlementQuery($filters);
        $data = DB::query()->fromSub($query, 'x')
            ->selectRaw('COUNT(*) as settlements')
            ->selectRaw('COALESCE(SUM(transfer_count),0) as transfers')
            ->selectRaw('COALESCE(SUM(transfer_value),0) as transfer_value')
            ->selectRaw('COALESCE(SUM(variance_value),0) as variance_value')
            ->first();

        return [
            'settlements' => (int) ($data->settlements ?? 0),
            'transfers' => (int) ($data->transfers ?? 0),
            'transfer_value' => (float) ($data->transfer_value ?? 0),
            'variance_value' => (float) ($data->variance_value ?? 0),
        ];
    }

    public function rows(array $filters = [], int $limit = 500)
    {
        return $this->settlementQuery($filters)->orderByDesc('s.settlement_date')->orderByDesc('s.id')->limit($limit)->get();
    }

    public function eligibleTransfers(array $filters = [])
    {
        $transferTable = $this->transferTable();
        $lineTable = $this->lineTable();
        $query = DB::table($transferTable . ' as st')
            ->leftJoin($lineTable . ' as sl', 'sl.transfer_id', '=', 'st.id')
            ->leftJoin('business as fb', 'fb.id', '=', 'st.from_business_id')
            ->leftJoin('business as tb', 'tb.id', '=', 'st.to_business_id')
            ->leftJoin('stock_transfer_new_inter_business_settlement_lines as settled', 'settled.transfer_id', '=', 'st.id')
            ->whereNull('settled.id')
            ->whereIn('st.status', ['received', 'completed'])
            ->whereNotNull('st.from_business_id')
            ->whereNotNull('st.to_business_id')
            ->whereColumn('st.from_business_id', '<>', 'st.to_business_id')
            ->select([
                'st.id', 'st.transfer_no', 'st.transaction_date', 'st.status',
                DB::raw('COALESCE(fb.name, st.from_business_id) as from_business_name'),
                DB::raw('COALESCE(tb.name, st.to_business_id) as to_business_name'),
            ])
            ->selectRaw('COALESCE(SUM(COALESCE(sl.quantity,0) * COALESCE(sl.unit_cost, sl.unit_price, 0)),0) as transfer_value')
            ->selectRaw('COALESCE(SUM((COALESCE(sl.received_quantity, sl.quantity, 0) - COALESCE(sl.quantity,0)) * COALESCE(sl.unit_cost, sl.unit_price, 0)),0) as variance_value')
            ->groupBy('st.id', 'st.transfer_no', 'st.transaction_date', 'st.status', 'fb.name', 'tb.name', 'st.from_business_id', 'st.to_business_id');

        foreach (['from_business_id', 'to_business_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('st.' . $field, $filters[$field]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('st.transaction_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('st.transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('st.transaction_date')->orderByDesc('st.id')->limit(500)->get();
    }

    public function createSettlement(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $transfers = $this->eligibleTransfers($data)->whereIn('id', $data['transfer_ids']);
            if ($transfers->count() !== count($data['transfer_ids'])) {
                throw new RuntimeException('One or more selected transfers are not eligible for settlement.');
            }

            $settlementId = DB::table('stock_transfer_new_inter_business_settlements')->insertGetId([
                'settlement_no' => $this->nextSettlementNo(),
                'from_business_id' => $data['from_business_id'],
                'to_business_id' => $data['to_business_id'],
                'settlement_date' => $data['settlement_date'],
                'transfer_count' => $transfers->count(),
                'transfer_value' => $transfers->sum('transfer_value'),
                'variance_value' => $transfers->sum('variance_value'),
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($transfers as $transfer) {
                DB::table('stock_transfer_new_inter_business_settlement_lines')->insert([
                    'settlement_id' => $settlementId,
                    'transfer_id' => $transfer->id,
                    'transfer_no' => $transfer->transfer_no,
                    'transfer_value' => $transfer->transfer_value,
                    'variance_value' => $transfer->variance_value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->audit($settlementId, 'created', $userId, $data['remarks'] ?? null);
            return $this->find($settlementId);
        });
    }

    public function find(int $id)
    {
        return $this->settlementQuery([])->where('s.id', $id)->firstOrFail();
    }

    public function lines(int $id)
    {
        return DB::table('stock_transfer_new_inter_business_settlement_lines')->where('settlement_id', $id)->orderBy('id')->get();
    }

    public function lineTotals(int $id): array
    {
        $data = DB::table('stock_transfer_new_inter_business_settlement_lines')
            ->where('settlement_id', $id)
            ->selectRaw('COUNT(*) as transfers')
            ->selectRaw('COALESCE(SUM(transfer_value),0) as transfer_value')
            ->selectRaw('COALESCE(SUM(variance_value),0) as variance_value')
            ->first();
        return ['transfers' => (int) ($data->transfers ?? 0), 'transfer_value' => (float) ($data->transfer_value ?? 0), 'variance_value' => (float) ($data->variance_value ?? 0)];
    }

    public function approve(int $id, int $userId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($id, $userId, $remarks) {
            $settlement = DB::table('stock_transfer_new_inter_business_settlements')->where('id', $id)->lockForUpdate()->first();
            if (! $settlement || $settlement->status !== 'draft') {
                throw new RuntimeException('Only draft settlements can be approved.');
            }
            DB::table('stock_transfer_new_inter_business_settlements')->where('id', $id)->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now(), 'updated_at' => now()]);
            $this->audit($id, 'approved', $userId, $remarks);
        });
    }

    public function cancel(int $id, int $userId, string $remarks): void
    {
        DB::transaction(function () use ($id, $userId, $remarks) {
            $settlement = DB::table('stock_transfer_new_inter_business_settlements')->where('id', $id)->lockForUpdate()->first();
            if (! $settlement || $settlement->status === 'approved') {
                throw new RuntimeException('Approved settlements cannot be cancelled here.');
            }
            DB::table('stock_transfer_new_inter_business_settlements')->where('id', $id)->update(['status' => 'cancelled', 'cancelled_by' => $userId, 'cancelled_at' => now(), 'cancel_reason' => $remarks, 'updated_at' => now()]);
            $this->audit($id, 'cancelled', $userId, $remarks);
        });
    }

    protected function settlementQuery(array $filters)
    {
        $query = DB::table('stock_transfer_new_inter_business_settlements as s')
            ->leftJoin('business as fb', 'fb.id', '=', 's.from_business_id')
            ->leftJoin('business as tb', 'tb.id', '=', 's.to_business_id')
            ->select(['s.*', DB::raw('COALESCE(fb.name, s.from_business_id) as from_business_name'), DB::raw('COALESCE(tb.name, s.to_business_id) as to_business_name')]);

        foreach (['from_business_id', 'to_business_id', 'status'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('s.' . $field, $filters[$field]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('s.settlement_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('s.settlement_date', '<=', $filters['date_to']);
        }
        return $query;
    }

    protected function transferTable(): string
    {
        return Schema::hasTable('stock_transfer_new_transfers') ? 'stock_transfer_new_transfers' : 'stn_transfers';
    }

    protected function lineTable(): string
    {
        return Schema::hasTable('stock_transfer_new_transfer_lines') ? 'stock_transfer_new_transfer_lines' : 'stn_transfer_lines';
    }

    protected function nextSettlementNo(): string
    {
        return 'IBS-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    protected function audit(int $settlementId, string $action, int $userId, ?string $remarks = null): void
    {
        DB::table('stock_transfer_new_inter_business_settlement_audits')->insert([
            'settlement_id' => $settlementId,
            'action' => $action,
            'remarks' => $remarks,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
