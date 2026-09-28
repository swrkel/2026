<?php

namespace Modules\POS\Services;

class POSShiftService extends POSBaseService
{
    public function list()
    {
        if (! $this->tableExists('pos_register_sessions')) {
            return collect();
        }

        $sessionColumns = array_flip($this->columns('pos_register_sessions'));
        $registerColumns = $this->tableExists('pos_registers')
            ? array_flip($this->columns('pos_registers'))
            : [];

        $query = $this->connection()->table('pos_register_sessions as s')->select('s.*');
        $joinedRegisters = isset($sessionColumns['register_id'], $registerColumns['id']);

        if ($joinedRegisters) {
            $query->leftJoin('pos_registers as r', 'r.id', '=', 's.register_id');
        }

        if ($joinedRegisters && isset($registerColumns['name'])) {
            $query->selectRaw('r.name as register_name');
        } else {
            $query->selectRaw('NULL as register_name');
        }

        if ($joinedRegisters && isset($registerColumns['code'])) {
            $query->selectRaw('r.code as register_code');
        } else {
            $query->selectRaw('NULL as register_code');
        }

        if (isset($sessionColumns['business_id']) && $this->businessId()) {
            $query->where('s.business_id', $this->businessId());
        }

        if (isset($sessionColumns['id'])) {
            $query->orderByDesc('s.id');
        } elseif (isset($sessionColumns['opened_at'])) {
            $query->orderByDesc('s.opened_at');
        }

        return $query->paginate(25);
    }

    public function current(?int $registerId = null): ?object
    {
        if (! $this->tableExists('pos_register_sessions')) {
            return null;
        }

        $columns = array_flip($this->columns('pos_register_sessions'));
        $query = $this->connection()->table('pos_register_sessions');

        if (isset($columns['status'])) {
            $query->where('status', 'open');
        }

        if ($registerId && isset($columns['register_id'])) {
            $query->where('register_id', $registerId);
        }

        if (isset($columns['business_id']) && $this->businessId()) {
            $query->where('business_id', $this->businessId());
        }

        if (isset($columns['id'])) {
            $query->orderByDesc('id');
        } elseif (isset($columns['opened_at'])) {
            $query->orderByDesc('opened_at');
        }

        return $query->first();
    }

    public function find(int $id): ?object
    {
        if (! $this->tableExists('pos_register_sessions') || ! $this->hasColumn('pos_register_sessions', 'id')) {
            return null;
        }

        return $this->connection()->table('pos_register_sessions')->where('id', $id)->first();
    }

    public function open(array $input): int
    {
        if (! $this->tableExists('pos_register_sessions')) {
            return 0;
        }

        $registerId = (int) ($input['register_id'] ?? 0);
        if ($registerId > 0 && $this->current($registerId)) {
            return 0;
        }

        $data = $this->onlyExistingColumns('pos_register_sessions', [
            'business_id' => $this->businessId(),
            'business_location_id' => $input['business_location_id'] ?? $this->locationId(),
            'register_id' => $registerId ?: null,
            'session_no' => $this->nextSessionNo(),
            'opened_at' => $input['opened_at'] ?? $this->nowString(),
            'opening_amount' => $input['opening_amount'] ?? 0,
            'expected_closing_amount' => 0,
            'actual_closing_amount' => 0,
            'variance_amount' => 0,
            'status' => 'open',
            'opened_by' => $this->userId(),
            'note' => $input['note'] ?? null,
            'created_at' => $this->nowString(),
            'updated_at' => $this->nowString(),
        ]);

        return $data === []
            ? 0
            : (int) $this->connection()->table('pos_register_sessions')->insertGetId($data);
    }

    public function close(int $sessionId, array $input): void
    {
        if (! $this->tableExists('pos_register_sessions') || ! $this->hasColumn('pos_register_sessions', 'id')) {
            return;
        }

        $summary = $this->summary($sessionId);
        $expected = (float) ($summary['expected'] ?? 0);
        $actual = (float) ($input['actual_closing_amount'] ?? 0);
        $data = $this->onlyExistingColumns('pos_register_sessions', [
            'closed_at' => $input['closed_at'] ?? $this->nowString(),
            'expected_closing_amount' => $expected,
            'actual_closing_amount' => $actual,
            'variance_amount' => $actual - $expected,
            'status' => 'closed',
            'closed_by' => $this->userId(),
            'closing_note' => $input['closing_note'] ?? null,
            'approval_status' => abs($actual - $expected) > 0 ? 'pending' : 'approved',
            'updated_at' => $this->nowString(),
        ]);

        if ($data !== []) {
            $this->connection()->table('pos_register_sessions')->where('id', $sessionId)->update($data);
        }
    }

    public function dashboard(): array
    {
        $openShifts = 0;
        $closedToday = 0;

        if ($this->tableExists('pos_register_sessions')) {
            $columns = array_flip($this->columns('pos_register_sessions'));

            if (isset($columns['status'])) {
                $openQuery = $this->connection()->table('pos_register_sessions')->where('status', 'open');
                $closedQuery = $this->connection()->table('pos_register_sessions')->where('status', 'closed');

                if (isset($columns['business_id']) && $this->businessId()) {
                    $openQuery->where('business_id', $this->businessId());
                    $closedQuery->where('business_id', $this->businessId());
                }

                $openShifts = $openQuery->count();

                if (isset($columns['closed_at'])) {
                    $closedQuery->whereDate('closed_at', now()->toDateString());
                }

                $closedToday = $closedQuery->count();
            }
        }

        return [
            'open_shifts' => $openShifts,
            'closed_today' => $closedToday,
            'cash_in_today' => $this->movementByDate('cash_in'),
            'cash_out_today' => $this->movementByDate('cash_out'),
        ];
    }

    public function expectedCash(int $sessionId, float $opening = 0): float
    {
        $cashIn = $this->sumMovement($sessionId, 'cash_in');
        $cashOut = $this->sumMovement($sessionId, 'cash_out') + $this->sumMovement($sessionId, 'refund');
        $cashSales = $this->cashSales($sessionId);

        return $opening + $cashSales + $cashIn - $cashOut;
    }

    public function summary(int $sessionId): array
    {
        $session = $this->find($sessionId);
        $opening = $session ? (float) ($session->opening_amount ?? 0) : 0;
        $cashSales = $this->cashSales($sessionId);
        $cardSales = $this->paymentSales($sessionId, ['card', 'credit_card']);
        $creditSales = $this->creditSales($sessionId);
        $refunds = $this->sumMovement($sessionId, 'refund');
        $cashIn = $this->sumMovement($sessionId, 'cash_in');
        $cashOut = $this->sumMovement($sessionId, 'cash_out');
        $expected = $opening + $cashSales + $cashIn - $cashOut - $refunds;

        return [
            'session' => $session,
            'opening' => $opening,
            'cash_sales' => $cashSales,
            'card_sales' => $cardSales,
            'credit_sales' => $creditSales,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'refunds' => $refunds,
            'expected' => $expected,
            'actual' => $session ? (float) ($session->actual_closing_amount ?? 0) : 0,
            'variance' => $session ? (float) ($session->variance_amount ?? 0) : 0,
            'sales_count' => $this->salesCount($sessionId),
        ];
    }

    protected function sumMovement(int $sessionId, string $type): float
    {
        if (! $this->tableExists('pos_cash_movements')) {
            return 0.0;
        }

        $columns = array_flip($this->columns('pos_cash_movements'));
        if (! isset($columns['amount'], $columns['movement_type'], $columns['register_session_id'])) {
            return 0.0;
        }

        return (float) $this->connection()->table('pos_cash_movements')
            ->where('register_session_id', $sessionId)
            ->where('movement_type', $type)
            ->sum('amount');
    }

    protected function movementByDate(string $type): float
    {
        if (! $this->tableExists('pos_cash_movements')) {
            return 0.0;
        }

        $columns = array_flip($this->columns('pos_cash_movements'));
        if (! isset($columns['amount'], $columns['movement_type'])) {
            return 0.0;
        }

        $query = $this->connection()->table('pos_cash_movements')->where('movement_type', $type);

        if (isset($columns['business_id']) && $this->businessId()) {
            $query->where('business_id', $this->businessId());
        }

        if (isset($columns['transaction_date'])) {
            $query->whereDate('transaction_date', now()->toDateString());
        } elseif (isset($columns['created_at'])) {
            $query->whereDate('created_at', now()->toDateString());
        }

        return (float) $query->sum('amount');
    }

    protected function cashSales(int $sessionId): float
    {
        return $this->paymentSales($sessionId, ['cash']);
    }

    protected function paymentSales(int $sessionId, array $methods): float
    {
        if (! $this->tableExists('pos_payments')) {
            return 0.0;
        }

        $paymentColumns = array_flip($this->columns('pos_payments'));
        if (! isset($paymentColumns['amount'])) {
            return 0.0;
        }

        $query = $this->connection()->table('pos_payments as p');
        $scoped = false;

        if (isset($paymentColumns['register_session_id'])) {
            $query->where('p.register_session_id', $sessionId);
            $scoped = true;
        } elseif ($this->tableExists('pos_sales')) {
            $salesColumns = array_flip($this->columns('pos_sales'));
            $paymentSaleKey = isset($paymentColumns['sale_id'])
                ? 'sale_id'
                : (isset($paymentColumns['pos_sale_id']) ? 'pos_sale_id' : null);
            $salesSessionKey = isset($salesColumns['session_id'])
                ? 'session_id'
                : (isset($salesColumns['register_session_id']) ? 'register_session_id' : null);

            if ($paymentSaleKey && $salesSessionKey && isset($salesColumns['id'])) {
                $query->join('pos_sales as s', 's.id', '=', 'p.' . $paymentSaleKey)
                    ->where('s.' . $salesSessionKey, $sessionId);
                $scoped = true;
            }
        }

        if (! $scoped) {
            return 0.0;
        }

        $methodColumn = isset($paymentColumns['payment_method'])
            ? 'payment_method'
            : (isset($paymentColumns['method']) ? 'method' : null);

        if ($methodColumn) {
            $query->whereIn('p.' . $methodColumn, $methods);
        }

        return (float) $query->sum('p.amount');
    }

    protected function creditSales(int $sessionId): float
    {
        if (! $this->tableExists('pos_sales')) {
            return 0.0;
        }

        $columns = array_flip($this->columns('pos_sales'));
        $sessionColumn = isset($columns['session_id'])
            ? 'session_id'
            : (isset($columns['register_session_id']) ? 'register_session_id' : null);
        $amountColumn = isset($columns['balance_amount'])
            ? 'balance_amount'
            : (isset($columns['total_amount']) ? 'total_amount' : null);

        if (! $sessionColumn || ! $amountColumn) {
            return 0.0;
        }

        $query = $this->connection()->table('pos_sales')->where($sessionColumn, $sessionId);

        if (isset($columns['payment_status'])) {
            $query->whereIn('payment_status', ['due', 'credit', 'partial']);
        }

        return (float) $query->sum($amountColumn);
    }

    protected function salesCount(int $sessionId): int
    {
        if (! $this->tableExists('pos_sales')) {
            return 0;
        }

        $columns = array_flip($this->columns('pos_sales'));
        $sessionColumn = isset($columns['session_id'])
            ? 'session_id'
            : (isset($columns['register_session_id']) ? 'register_session_id' : null);

        if (! $sessionColumn) {
            return 0;
        }

        $query = $this->connection()->table('pos_sales')->where($sessionColumn, $sessionId);

        if (isset($columns['status'])) {
            $query->where(function ($nested) {
                $nested->whereNull('status')->orWhereNotIn('status', ['void', 'cancelled']);
            });
        }

        return (int) $query->count();
    }

    protected function nextSessionNo(): string
    {
        $prefix = 'POSS-' . now()->format('Ymd') . '-';
        $next = 1;

        if ($this->tableExists('pos_register_sessions')) {
            $columns = array_flip($this->columns('pos_register_sessions'));
            $query = $this->connection()->table('pos_register_sessions');

            if (isset($columns['created_at'])) {
                $query->whereDate('created_at', now()->toDateString());
            } elseif (isset($columns['opened_at'])) {
                $query->whereDate('opened_at', now()->toDateString());
            }

            $next = $query->count() + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
