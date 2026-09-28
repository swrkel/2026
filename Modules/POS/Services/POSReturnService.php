<?php

namespace Modules\POS\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POSReturnService extends POSBaseService
{

    public function dashboardStats(Request $request): array
    {
        $from = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $to = $request->input('end_date') ?: now()->format('Y-m-d');
        $stats = [
            'returns_count' => 0,
            'refund_total' => 0,
            'pending_approval' => 0,
            'exchanges_count' => 0,
        ];

        $returnTable = $this->returnTable();
        if ($returnTable) {
            $columns = array_flip($this->columns($returnTable));
            $query = $this->connection()->table($returnTable);

            if (isset($columns['business_id']) && $this->businessId()) {
                $query->where('business_id', $this->businessId());
            }

            $dateColumn = isset($columns['return_date'])
                ? 'return_date'
                : (isset($columns['transaction_date']) ? 'transaction_date' : null);

            if ($dateColumn) {
                $query->whereDate($dateColumn, '>=', $from)
                    ->whereDate($dateColumn, '<=', $to);
            }

            $stats['returns_count'] = (clone $query)->count();

            if (isset($columns['total_amount'])) {
                $stats['refund_total'] = (float) (clone $query)->sum('total_amount');
            }

            if (isset($columns['approval_status'])) {
                $stats['pending_approval'] = (clone $query)
                    ->where('approval_status', 'pending')
                    ->count();
            } elseif (isset($columns['status'])) {
                $stats['pending_approval'] = (clone $query)
                    ->where('status', 'pending')
                    ->count();
            }
        }

        if ($this->tableExists('pos_sale_exchanges')) {
            $columns = array_flip($this->columns('pos_sale_exchanges'));
            $query = $this->connection()->table('pos_sale_exchanges');

            if (isset($columns['business_id']) && $this->businessId()) {
                $query->where('business_id', $this->businessId());
            }

            if (isset($columns['exchange_date'])) {
                $query->whereDate('exchange_date', '>=', $from)
                    ->whereDate('exchange_date', '<=', $to);
            }

            $stats['exchanges_count'] = $query->count();
        }

        return $stats;
    }
    public function returnsList(Request $request)
    {
        $returnTable = $this->returnTable();

        if (! $returnTable) {
            return collect();
        }

        $returnColumns = array_flip($this->columns($returnTable));
        $salesColumns = $this->tableExists('pos_sales')
            ? array_flip($this->columns('pos_sales'))
            : [];

        $query = $this->connection()->table($returnTable . ' as r')->select('r.*');
        $joinedSales = isset($returnColumns['sale_id'], $salesColumns['id']);

        if ($joinedSales) {
            $query->leftJoin('pos_sales as s', 's.id', '=', 'r.sale_id');
        }

        $this->addReturnListAliases($query, $returnColumns, $salesColumns, $joinedSales);

        if (isset($returnColumns['business_id']) && $this->businessId()) {
            $query->where('r.business_id', $this->businessId());
        }

        $dateColumn = isset($returnColumns['return_date'])
            ? 'return_date'
            : (isset($returnColumns['transaction_date']) ? 'transaction_date' : null);

        if ($dateColumn && $request->filled('start_date')) {
            $query->whereDate('r.' . $dateColumn, '>=', $request->input('start_date'));
        }

        if ($dateColumn && $request->filled('end_date')) {
            $query->whereDate('r.' . $dateColumn, '<=', $request->input('end_date'));
        }

        if ($request->filled('q')) {
            $like = '%' . trim((string) $request->input('q')) . '%';
            $conditions = [];

            foreach (['return_no', 'reference_no'] as $column) {
                if (isset($returnColumns[$column])) {
                    $conditions[] = ['r.' . $column, $like];
                }
            }

            if ($joinedSales) {
                foreach (['sale_no', 'invoice_no', 'customer_name'] as $column) {
                    if (isset($salesColumns[$column])) {
                        $conditions[] = ['s.' . $column, $like];
                    }
                }
            }

            if ($conditions !== []) {
                $query->where(function ($nested) use ($conditions) {
                    foreach ($conditions as $index => [$column, $value]) {
                        $index === 0
                            ? $nested->where($column, 'like', $value)
                            : $nested->orWhere($column, 'like', $value);
                    }
                });
            }
        }

        if (isset($returnColumns['id'])) {
            $query->orderByDesc('r.id');
        } elseif ($dateColumn) {
            $query->orderByDesc('r.' . $dateColumn);
        } elseif (isset($returnColumns['return_no'])) {
            $query->orderByDesc('r.return_no');
        }

        return $query->paginate(25);
    }

    public function saleForReturn(int $saleId): ?object
    {
        if (! $this->tableExists('pos_sales') || ! $this->hasColumn('pos_sales', 'id')) {
            return null;
        }

        $sale = $this->connection()->table('pos_sales')->where('id', $saleId)->first();

        if (! $sale) {
            return null;
        }

        $lines = collect();
        if ($this->tableExists('pos_sale_lines')) {
            $lineColumns = array_flip($this->columns('pos_sale_lines'));
            $foreignKeys = array_values(array_filter(
                ['sale_id', 'pos_sale_id'],
                static fn (string $column): bool => isset($lineColumns[$column])
            ));

            if ($foreignKeys !== []) {
                $lines = $this->connection()->table('pos_sale_lines')
                    ->where(function ($query) use ($foreignKeys, $saleId) {
                        foreach ($foreignKeys as $index => $column) {
                            $index === 0
                                ? $query->where($column, $saleId)
                                : $query->orWhere($column, $saleId);
                        }
                    })
                    ->get();
            }
        }

        $sale->lines = $lines;

        return $sale;
    }

    public function recentSales()
    {
        if (! $this->tableExists('pos_sales') || ! $this->hasColumn('pos_sales', 'id')) {
            return collect();
        }

        $columns = array_flip($this->columns('pos_sales'));
        $query = $this->connection()->table('pos_sales as s')->select('s.*');

        if (isset($columns['business_id']) && $this->businessId()) {
            $query->where('s.business_id', $this->businessId());
        }

        if (isset($columns['status'])) {
            $query->where(function ($nested) {
                $nested->whereNull('s.status')->orWhereNotIn('s.status', ['void', 'cancelled']);
            });
        }

        if (! isset($columns['sale_no'])) {
            if (isset($columns['invoice_no'])) {
                $query->selectRaw('s.invoice_no as sale_no');
            } else {
                $query->selectRaw("CONCAT('POS-', s.id) as sale_no");
            }
        }

        if (! isset($columns['invoice_no'])) {
            $query->selectRaw('NULL as invoice_no');
        }

        if (! isset($columns['customer_name'])) {
            $query->selectRaw('NULL as customer_name');
        }

        if (! isset($columns['sale_date'])) {
            if (isset($columns['created_at'])) {
                $query->selectRaw('s.created_at as sale_date');
            } else {
                $query->selectRaw('NULL as sale_date');
            }
        }

        if (! isset($columns['total_amount'])) {
            $query->selectRaw('0 as total_amount');
        }

        if (! isset($columns['status'])) {
            $query->selectRaw("'final' as status");
        }

        return $query->orderByDesc('s.id')->limit(50)->get();
    }

    public function storeReturn(array $input): array
    {
        if (!$this->tableExists('pos_sale_returns') || !$this->tableExists('pos_sales')) {
            return ['success' => false, 'message' => 'Return tables are not ready. Please run S351 SQL.'];
        }
        $saleId = (int)($input['sale_id'] ?? 0);
        $sale = $this->saleForReturn($saleId);
        if (!$sale) { return ['success' => false, 'message' => 'Selected sale was not found.']; }
        $returnLines = $input['lines'] ?? [];
        $total = 0; $clean = [];
        foreach ($sale->lines as $line) {
            $qty = (float)($returnLines[$line->id]['quantity'] ?? 0);
            if ($qty <= 0) { continue; }
            $soldQty = (float)($line->quantity ?? 0);
            if ($qty > $soldQty) { $qty = $soldQty; }
            $unitPrice = (float)($line->unit_price ?? 0);
            $lineTotal = round($qty * $unitPrice, 4);
            $total += $lineTotal;
            $clean[] = ['source' => $line, 'quantity' => $qty, 'unit_price' => $unitPrice, 'line_total' => $lineTotal];
        }
        if (empty($clean)) { return ['success' => false, 'message' => 'Please enter at least one return quantity.']; }
        return DB::transaction(function () use ($sale, $clean, $total, $input) {
            $now = $this->nowString();
            $returnNo = 'RET-'.date('Ymd-His').'-'.random_int(100,999);
            $returnId = DB::table('pos_sale_returns')->insertGetId($this->onlyExistingColumns('pos_sale_returns', [
                'business_id' => $this->businessId(),
                'business_location_id' => $sale->business_location_id ?? $this->locationId(),
                'sale_id' => $sale->id,
                'return_no' => $returnNo,
                'return_date' => $input['return_date'] ?? $now,
                'refund_method' => $input['refund_method'] ?? 'cash',
                'total_amount' => round($total, 4),
                'note' => $input['note'] ?? null,
                'status' => (($input['approval_status'] ?? 'approved') === 'pending') ? 'pending' : 'final',
                'approval_status' => $input['approval_status'] ?? 'approved',
                'approved_by' => (($input['approval_status'] ?? 'approved') === 'pending') ? null : $this->userId(),
                'approved_at' => (($input['approval_status'] ?? 'approved') === 'pending') ? null : $now,
                'created_by' => $this->userId(),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            foreach ($clean as $line) {
                $src = $line['source'];
                DB::table('pos_sale_return_lines')->insert($this->onlyExistingColumns('pos_sale_return_lines', [
                    'return_id' => $returnId,
                    'sale_line_id' => $src->id,
                    'product_id' => $src->product_id ?? null,
                    'product_name' => $src->product_name ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
                $this->restoreStock((int)($src->product_id ?? 0), (float)$line['quantity'], $returnId, $now);
            }
            if ($this->tableExists('pos_cash_movements')) {
                DB::table('pos_cash_movements')->insert($this->onlyExistingColumns('pos_cash_movements', [
                    'business_id' => $this->businessId(),
                    'business_location_id' => $sale->business_location_id ?? $this->locationId(),
                    'register_session_id' => $sale->session_id ?? null,
                    'movement_type' => 'refund',
                    'amount' => round($total, 4),
                    'transaction_date' => $input['return_date'] ?? $now,
                    'reference_no' => $returnNo,
                    'note' => $input['note'] ?? 'POS return refund',
                    'created_by' => $this->userId(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
            return ['success' => true, 'return_id' => $returnId, 'message' => 'Return saved successfully.'];
        });
    }

    public function voidSale(int $saleId, ?string $reason = null): array
    {
        if (!$this->tableExists('pos_sales')) { return ['success' => false, 'message' => 'Sales table is not ready.']; }
        $sale = $this->saleForReturn($saleId);
        if (!$sale) { return ['success' => false, 'message' => 'Sale not found.']; }
        if (($sale->status ?? '') === 'void') { return ['success' => false, 'message' => 'This sale is already voided.']; }
        return DB::transaction(function () use ($sale, $reason) {
            $now = $this->nowString();
            DB::table('pos_sales')->where('id', $sale->id)->update($this->onlyExistingColumns('pos_sales', [
                'status' => 'void', 'void_reason' => $reason, 'voided_at' => $now, 'voided_by' => $this->userId(), 'updated_at' => $now,
            ]));
            foreach ($sale->lines as $line) { $this->restoreStock((int)($line->product_id ?? 0), (float)($line->quantity ?? 0), $sale->id, $now, 'void'); }
            return ['success' => true, 'message' => 'Sale voided and stock restored.'];
        });
    }


    public function approveReturn(int $returnId, string $status, ?string $note = null): array
    {
        if (!$this->tableExists('pos_sale_returns')) { return ['success' => false, 'message' => 'Return table is not ready.']; }
        $return = DB::table('pos_sale_returns')->where('id', $returnId)->first();
        if (!$return) { return ['success' => false, 'message' => 'Return record not found.']; }
        $now = $this->nowString();
        $update = $this->onlyExistingColumns('pos_sale_returns', [
            'approval_status' => $status,
            'status' => $status === 'approved' ? 'final' : ($status === 'rejected' ? 'rejected' : 'pending'),
            'approval_note' => $note,
            'approved_by' => $status === 'approved' ? $this->userId() : null,
            'approved_at' => $status === 'approved' ? $now : null,
            'updated_at' => $now,
        ]);
        DB::table('pos_sale_returns')->where('id', $returnId)->update($update);
        return ['success' => true, 'message' => 'Return approval updated.'];
    }

    public function exchangesList(Request $request)
    {
        if (!$this->tableExists('pos_sale_exchanges')) { return collect(); }
        $query = DB::table('pos_sale_exchanges as e')
            ->leftJoin('pos_sales as s', 's.id', '=', 'e.sale_id')
            ->select('e.*', 's.sale_no', 's.invoice_no', 's.customer_name');
        if ($this->businessId() && in_array('business_id', $this->columns('pos_sale_exchanges'))) {
            $query->where('e.business_id', $this->businessId());
        }
        if ($request->filled('q')) {
            $q = '%'.$request->input('q').'%';
            $query->where(function ($w) use ($q) {
                $w->where('e.exchange_no', 'like', $q)->orWhere('s.sale_no', 'like', $q)->orWhere('s.invoice_no', 'like', $q);
            });
        }
        return $query->orderByDesc('e.id')->paginate(25);
    }

    public function exchangeProducts()
    {
        if (!$this->tableExists('pos_products')) { return collect(); }
        return DB::table('pos_products')
            ->select('id', 'name', 'sku', 'barcode', 'selling_price', 'current_stock')
            ->orderBy('name')->limit(500)->get();
    }

    public function storeExchange(array $input): array
    {
        if (!$this->tableExists('pos_sale_exchanges') || !$this->tableExists('pos_sales')) {
            return ['success' => false, 'message' => 'Exchange tables are not ready. Please run S368 SQL.'];
        }
        $saleId = (int) ($input['sale_id'] ?? 0);
        $sale = $this->saleForReturn($saleId);
        if (!$sale) { return ['success' => false, 'message' => 'Selected sale was not found.']; }
        $returnLines = $input['return_lines'] ?? [];
        $newLines = $input['new_lines'] ?? [];
        $returnTotal = 0; $cleanReturns = [];
        foreach ($sale->lines as $line) {
            $qty = (float) ($returnLines[$line->id]['quantity'] ?? 0);
            if ($qty <= 0) { continue; }
            $soldQty = (float) ($line->quantity ?? 0);
            if ($qty > $soldQty) { $qty = $soldQty; }
            $unitPrice = (float) ($line->unit_price ?? 0);
            $lineTotal = round($qty * $unitPrice, 4);
            $returnTotal += $lineTotal;
            $cleanReturns[] = ['source' => $line, 'quantity' => $qty, 'unit_price' => $unitPrice, 'line_total' => $lineTotal];
        }
        $newTotal = 0; $cleanNew = [];
        foreach ($newLines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = (float) ($line['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) { continue; }
            $product = DB::table('pos_products')->where('id', $productId)->first();
            if (!$product) { continue; }
            $unitPrice = (float) ($line['unit_price'] ?? ($product->selling_price ?? 0));
            $lineTotal = round($qty * $unitPrice, 4);
            $newTotal += $lineTotal;
            $cleanNew[] = ['product' => $product, 'quantity' => $qty, 'unit_price' => $unitPrice, 'line_total' => $lineTotal];
        }
        if (empty($cleanReturns) && empty($cleanNew)) { return ['success' => false, 'message' => 'Please enter at least one return or replacement item.']; }
        return DB::transaction(function () use ($sale, $cleanReturns, $cleanNew, $returnTotal, $newTotal, $input) {
            $now = $this->nowString();
            $exchangeNo = 'EXC-'.date('Ymd-His').'-'.random_int(100,999);
            $difference = round($newTotal - $returnTotal, 4);
            $exchangeId = DB::table('pos_sale_exchanges')->insertGetId($this->onlyExistingColumns('pos_sale_exchanges', [
                'business_id' => $this->businessId(),
                'business_location_id' => $sale->business_location_id ?? $this->locationId(),
                'sale_id' => $sale->id,
                'exchange_no' => $exchangeNo,
                'exchange_date' => $input['exchange_date'] ?? $now,
                'return_total' => round($returnTotal, 4),
                'new_total' => round($newTotal, 4),
                'difference_amount' => $difference,
                'payment_method' => $input['payment_method'] ?? 'cash',
                'note' => $input['note'] ?? null,
                'status' => 'final',
                'created_by' => $this->userId(),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            foreach ($cleanReturns as $line) {
                $src = $line['source'];
                DB::table('pos_sale_exchange_lines')->insert($this->onlyExistingColumns('pos_sale_exchange_lines', [
                    'exchange_id' => $exchangeId, 'line_type' => 'return', 'sale_line_id' => $src->id,
                    'product_id' => $src->product_id ?? null, 'product_name' => $src->product_name ?? null,
                    'quantity' => $line['quantity'], 'unit_price' => $line['unit_price'], 'line_total' => $line['line_total'],
                    'created_at' => $now, 'updated_at' => $now,
                ]));
                $this->restoreStock((int)($src->product_id ?? 0), (float)$line['quantity'], $exchangeId, $now, 'exchange_return');
            }
            foreach ($cleanNew as $line) {
                $product = $line['product'];
                DB::table('pos_sale_exchange_lines')->insert($this->onlyExistingColumns('pos_sale_exchange_lines', [
                    'exchange_id' => $exchangeId, 'line_type' => 'new', 'product_id' => $product->id,
                    'product_name' => $product->name ?? null, 'quantity' => $line['quantity'], 'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'], 'created_at' => $now, 'updated_at' => $now,
                ]));
                $this->reduceStock((int)$product->id, (float)$line['quantity'], $exchangeId, $now);
            }
            if ($this->tableExists('pos_cash_movements') && abs($difference) > 0) {
                DB::table('pos_cash_movements')->insert($this->onlyExistingColumns('pos_cash_movements', [
                    'business_id' => $this->businessId(), 'business_location_id' => $sale->business_location_id ?? $this->locationId(),
                    'register_session_id' => $sale->session_id ?? null, 'movement_type' => $difference >= 0 ? 'exchange_payment' : 'exchange_refund',
                    'amount' => abs($difference), 'transaction_date' => $input['exchange_date'] ?? $now, 'reference_no' => $exchangeNo,
                    'note' => $input['note'] ?? 'POS exchange difference', 'created_by' => $this->userId(), 'created_at' => $now, 'updated_at' => $now,
                ]));
            }
            return ['success' => true, 'exchange_id' => $exchangeId, 'message' => 'Exchange saved successfully.'];
        });
    }

    public function exportReturnsCsv(Request $request)
    {
        $rows = $this->returnsList($request);
        $filename = 'pos_returns_'.date('Ymd_His').'.csv';
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Return No', 'Sale No', 'Date', 'Customer', 'Refund Method', 'Amount', 'Status', 'Approval']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->return_no ?? '', $row->sale_no ?? $row->invoice_no ?? '', $row->return_date ?? '',
                    $row->customer_name ?? 'Walk-in', $row->refund_method ?? '', $row->total_amount ?? 0,
                    $row->status ?? '', $row->approval_status ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function returnReceipt(int $returnId): ?object
    {
        $returnTable = $this->returnTable();

        if (! $returnTable) {
            return null;
        }

        $returnColumns = array_flip($this->columns($returnTable));
        $salesColumns = $this->tableExists('pos_sales')
            ? array_flip($this->columns('pos_sales'))
            : [];

        $query = $this->connection()->table($returnTable . ' as r')
            ->select('r.*')
            ->where('r.id', $returnId);

        $joinedSales = isset($returnColumns['sale_id'], $salesColumns['id']);
        if ($joinedSales) {
            $query->leftJoin('pos_sales as s', 's.id', '=', 'r.sale_id');
        }

        $this->addReturnListAliases($query, $returnColumns, $salesColumns, $joinedSales);

        $return = $query->first();
        if (! $return) {
            return null;
        }

        $lineTable = $this->returnLineTable();
        $return->lines = $lineTable && $this->hasColumn($lineTable, 'return_id')
            ? $this->connection()->table($lineTable)->where('return_id', $returnId)->get()
            : collect();

        return $return;
    }



    private function returnTable(): ?string
    {
        foreach (['pos_sale_returns', 'pos_returns'] as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    private function returnLineTable(): ?string
    {
        foreach (['pos_sale_return_lines', 'pos_return_lines'] as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    private function addReturnListAliases($query, array $returnColumns, array $salesColumns, bool $joinedSales): void
    {
        if (! isset($returnColumns['return_date'])) {
            if (isset($returnColumns['transaction_date'])) {
                $query->selectRaw('r.transaction_date as return_date');
            } else {
                $query->selectRaw('NULL as return_date');
            }
        }

        if (! isset($returnColumns['refund_method'])) {
            $query->selectRaw("'cash' as refund_method");
        }

        if (! isset($returnColumns['approval_status'])) {
            if (isset($returnColumns['status'])) {
                $query->selectRaw(
                    "CASE WHEN r.status = 'pending' THEN 'pending' WHEN r.status = 'rejected' THEN 'rejected' ELSE 'approved' END as approval_status"
                );
            } else {
                $query->selectRaw("'approved' as approval_status");
            }
        }

        if (! isset($returnColumns['status'])) {
            $query->selectRaw("'final' as status");
        }

        if (! isset($returnColumns['total_amount'])) {
            $query->selectRaw('0 as total_amount');
        }

        if ($joinedSales && isset($salesColumns['sale_no'])) {
            $query->addSelect('s.sale_no');
        } elseif ($joinedSales && isset($salesColumns['invoice_no'])) {
            $query->selectRaw('s.invoice_no as sale_no');
        } else {
            $query->selectRaw('NULL as sale_no');
        }

        if ($joinedSales && isset($salesColumns['invoice_no'])) {
            $query->addSelect('s.invoice_no');
        } else {
            $query->selectRaw('NULL as invoice_no');
        }

        if ($joinedSales && isset($salesColumns['customer_name'])) {
            $query->addSelect('s.customer_name');
        } elseif (! isset($returnColumns['customer_name'])) {
            $query->selectRaw('NULL as customer_name');
        }
    }

    private function reduceStock(int $productId, float $qty, int $refId, string $now): void
    {
        if ($productId <= 0 || $qty <= 0 || !$this->tableExists('pos_products')) { return; }
        $product = DB::table('pos_products')->where('id', $productId)->first();
        if (!$product) { return; }
        $stockColumn = in_array('current_stock', $this->columns('pos_products')) ? 'current_stock' : (in_array('stock_quantity', $this->columns('pos_products')) ? 'stock_quantity' : (in_array('stock_qty', $this->columns('pos_products')) ? 'stock_qty' : null));
        if ($stockColumn) {
            $before = (float)($product->{$stockColumn} ?? 0); $after = max(0, $before - $qty);
            DB::table('pos_products')->where('id',$productId)->update([$stockColumn => $after, 'updated_at' => $now]);
            if ($this->tableExists('pos_stock_movements')) {
                DB::table('pos_stock_movements')->insert($this->onlyExistingColumns('pos_stock_movements', [
                    'product_id' => $productId, 'movement_type' => 'exchange_issue', 'reference_type' => 'exchange', 'reference_id' => $refId,
                    'quantity' => -1 * $qty, 'stock_before' => $before, 'stock_after' => $after,
                    'note' => 'POS exchange replacement stock issue', 'created_by' => $this->userId(), 'created_at' => $now, 'updated_at' => $now,
                ]));
            }
        }
    }

    private function restoreStock(int $productId, float $qty, int $refId, string $now, string $type = 'return'): void
    {
        if ($productId <= 0 || $qty <= 0 || !$this->tableExists('pos_products')) { return; }
        $product = DB::table('pos_products')->where('id', $productId)->first();
        if (!$product) { return; }
        $stockColumn = in_array('current_stock', $this->columns('pos_products')) ? 'current_stock' : (in_array('stock_quantity', $this->columns('pos_products')) ? 'stock_quantity' : (in_array('stock_qty', $this->columns('pos_products')) ? 'stock_qty' : null));
        if ($stockColumn) {
            $before = (float)($product->{$stockColumn} ?? 0); $after = $before + $qty;
            DB::table('pos_products')->where('id',$productId)->update([$stockColumn => $after, 'updated_at' => $now]);
            if ($this->tableExists('pos_stock_movements')) {
                DB::table('pos_stock_movements')->insert($this->onlyExistingColumns('pos_stock_movements', [
                    'product_id' => $productId, 'movement_type' => $type === 'void' ? 'void_restore' : 'sale_return', 'reference_type' => $type, 'reference_id' => $refId, 'quantity' => $qty, 'stock_before' => $before, 'stock_after' => $after, 'note' => 'POS '.$type.' stock restore', 'created_by' => $this->userId(), 'created_at' => $now, 'updated_at' => $now,
                ]));
            }
        }
    }
}
