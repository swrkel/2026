<?php
namespace Modules\POS\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\POS\Entities\POSSyncQueue;
use Modules\POS\Entities\POSSyncConflict;

class POSOfflineSyncService
{
    public function deviceUuid(): string
    {
        if (!session()->has('pos_device_uuid')) {
            session(['pos_device_uuid' => (string) Str::uuid()]);
        }
        return session('pos_device_uuid');
    }

    public function dashboard(): array
    {
        $hasQueue = Schema::hasTable('pos_offline_sync_queue');
        $hasConflicts = Schema::hasTable('pos_offline_sync_conflicts');
        $hasDevices = Schema::hasTable('pos_devices');

        $queue = ['pending' => 0, 'synced' => 0, 'failed' => 0, 'conflict' => 0];
        if ($hasQueue) {
            foreach (array_keys($queue) as $status) {
                $queue[$status] = (int) DB::table('pos_offline_sync_queue')->where('status', $status)->count();
            }
        }

        return [
            'device_uuid' => $this->deviceUuid(),
            'tables' => [
                'pos_offline_sync_queue' => $hasQueue,
                'pos_offline_sync_conflicts' => $hasConflicts,
                'pos_devices' => $hasDevices,
            ],
            'queue' => $queue,
            'conflicts_open' => $hasConflicts ? (int) DB::table('pos_offline_sync_conflicts')->whereNull('resolved_at')->count() : 0,
            'last_synced_at' => $hasQueue ? DB::table('pos_offline_sync_queue')->whereNotNull('synced_at')->max('synced_at') : null,
        ];
    }

    public function acceptQueueItem(array $payload): array
    {
        if (!Schema::hasTable('pos_offline_sync_queue')) {
            return ['ok' => false, 'message' => 'Offline sync SQL has not been installed.'];
        }

        $clientToken = $payload['client_token'] ?? null;
        if (!$clientToken) {
            return ['ok' => false, 'message' => 'Missing client_token.'];
        }

        $existing = POSSyncQueue::where('client_token', $clientToken)->first();
        if ($existing) {
            return [
                'ok' => true,
                'duplicate' => true,
                'queue_id' => $existing->id,
                'status' => $existing->status,
                'message' => 'Duplicate sync token ignored safely.',
            ];
        }

        $record = POSSyncQueue::create([
            'business_id' => session('business.id') ?? null,
            'location_id' => session('business_location_id') ?? null,
            'device_uuid' => $payload['device_uuid'] ?? $this->deviceUuid(),
            'terminal_code' => $payload['terminal_code'] ?? null,
            'client_token' => $clientToken,
            'offline_invoice_no' => $payload['offline_invoice_no'] ?? null,
            'transaction_type' => $payload['transaction_type'] ?? 'sale',
            'payload' => $payload,
            'status' => 'pending',
            'created_offline_at' => $payload['created_offline_at'] ?? now(),
            'created_by' => auth()->id(),
        ]);

        return ['ok' => true, 'duplicate' => false, 'queue_id' => $record->id, 'status' => 'pending'];
    }



    public function syncPending(int $limit = 25): array
    {
        if (!Schema::hasTable('pos_offline_sync_queue')) {
            return ['ok' => false, 'message' => 'Offline sync queue table missing.', 'processed' => 0, 'results' => []];
        }

        $items = POSSyncQueue::where('status', 'pending')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($items as $item) {
            $results[] = $this->processQueueItem($item);
        }

        return ['ok' => true, 'processed' => count($results), 'results' => $results];
    }

    protected function processQueueItem(POSSyncQueue $queue): array
    {
        $queue->attempt_count = (int) $queue->attempt_count + 1;
        $payload = is_array($queue->payload) ? $queue->payload : (json_decode((string) $queue->payload, true) ?: []);

        try {
            if (($queue->transaction_type ?: ($payload['transaction_type'] ?? 'sale')) !== 'sale') {
                return $this->markConflict($queue, 'unsupported_transaction', 'Only offline sales are enabled in S380. Returns and register close will be enabled in later sync stages.', $payload);
            }

            $validation = $this->validateOfflineSaleForPosting($payload);
            if (!$validation['ok']) {
                return $this->markConflict($queue, $validation['type'], $validation['message'], $payload, $validation['snapshot'] ?? []);
            }

            $result = DB::transaction(function () use ($queue, $payload) {
                $saleId = $this->postOfflineSale($queue, $payload);
                $serverInvoice = $this->serverInvoiceNo($saleId);

                $queue->server_invoice_no = $serverInvoice;
                $queue->server_response = ['sale_id' => $saleId, 'server_invoice_no' => $serverInvoice];
                $queue->status = 'synced';
                $queue->last_error = null;
                $queue->synced_at = now();
                $queue->save();

                return ['queue_id' => $queue->id, 'status' => 'synced', 'sale_id' => $saleId, 'server_invoice_no' => $serverInvoice];
            });

            return $result;
        } catch (\Throwable $e) {
            $queue->status = 'failed';
            $queue->last_error = $e->getMessage();
            $queue->failed_at = now();
            $queue->save();
            return ['queue_id' => $queue->id, 'status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    protected function validateOfflineSaleForPosting(array $payload): array
    {
        if (!Schema::hasTable('pos_sales') || !Schema::hasTable('pos_sale_lines')) {
            return ['ok' => false, 'type' => 'missing_table', 'message' => 'POS sales tables are missing. Install POS core SQL first.'];
        }

        if (empty($payload['client_token'])) {
            return ['ok' => false, 'type' => 'missing_token', 'message' => 'Offline sale has no client token.'];
        }

        if (Schema::hasColumn('pos_sales', 'offline_client_token')) {
            $duplicate = DB::table('pos_sales')->where('offline_client_token', $payload['client_token'])->first();
            if ($duplicate) {
                return ['ok' => false, 'type' => 'duplicate_sale', 'message' => 'This offline sale was already posted to server.', 'snapshot' => ['sale_id' => $duplicate->id]];
            }
        }

        $items = $payload['items'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            return ['ok' => false, 'type' => 'empty_sale', 'message' => 'Offline sale has no product lines.'];
        }

        foreach ($items as $line) {
            $productId = $line['product_id'] ?? null;
            $qty = (float) ($line['quantity'] ?? $line['qty'] ?? 0);
            if (!$productId || $qty <= 0) {
                return ['ok' => false, 'type' => 'invalid_line', 'message' => 'One or more sale lines have invalid product or quantity.'];
            }

            if (Schema::hasTable('pos_products') && Schema::hasColumn('pos_products', 'stock_qty')) {
                $product = DB::table('pos_products')->where('id', $productId)->lockForUpdate()->first();
                if (!$product) {
                    return ['ok' => false, 'type' => 'missing_product', 'message' => 'Product not found during server sync.', 'snapshot' => ['product_id' => $productId]];
                }
                if ((float) $product->stock_qty < $qty && empty($payload['allow_negative_stock_by_manager'])) {
                    return ['ok' => false, 'type' => 'stock_conflict', 'message' => 'Insufficient stock during server sync. Manager review required.', 'snapshot' => ['product_id' => $productId, 'server_stock' => (float) $product->stock_qty, 'offline_qty' => $qty]];
                }
            }
        }

        if (!empty($payload['credit_sale']) && !empty($payload['customer_id'])) {
            $creditCheck = $this->validateCustomerCredit($payload);
            if (!$creditCheck['ok']) {
                return $creditCheck;
            }
        }

        return ['ok' => true];
    }

    protected function validateCustomerCredit(array $payload): array
    {
        $customerId = $payload['customer_id'] ?? null;
        $total = (float) ($payload['total_amount'] ?? $payload['total'] ?? 0);
        $table = Schema::hasTable('customers') ? 'customers' : (Schema::hasTable('pos_customers') ? 'pos_customers' : null);
        if (!$table || !$customerId) {
            return ['ok' => false, 'type' => 'customer_missing', 'message' => 'Credit customer could not be validated during server sync.'];
        }

        $customer = DB::table($table)->where('id', $customerId)->first();
        if (!$customer) {
            return ['ok' => false, 'type' => 'customer_missing', 'message' => 'Credit customer not found during server sync.'];
        }

        $creditLimit = property_exists($customer, 'credit_limit') ? (float) $customer->credit_limit : 0;
        $balance = property_exists($customer, 'balance') ? (float) $customer->balance : 0;
        if ($creditLimit > 0 && ($balance + $total) > $creditLimit && empty($payload['allow_credit_over_limit_by_manager'])) {
            return ['ok' => false, 'type' => 'credit_limit_conflict', 'message' => 'Customer credit limit exceeded during server sync.', 'snapshot' => ['customer_id' => $customerId, 'credit_limit' => $creditLimit, 'balance' => $balance, 'offline_sale_total' => $total]];
        }

        return ['ok' => true];
    }

    protected function postOfflineSale(POSSyncQueue $queue, array $payload): int
    {
        $items = $payload['items'] ?? [];
        $subtotal = (float) ($payload['subtotal'] ?? collect($items)->sum(function ($line) {
            return (float) ($line['quantity'] ?? $line['qty'] ?? 0) * (float) ($line['unit_price'] ?? $line['price'] ?? 0);
        }));
        $discount = (float) ($payload['discount_amount'] ?? 0);
        $tax = (float) ($payload['tax_amount'] ?? 0);
        $total = (float) ($payload['total_amount'] ?? ($subtotal - $discount + $tax));
        $paid = (float) ($payload['paid_amount'] ?? $total);
        $balance = max(0, $total - $paid);
        $paymentStatus = $balance > 0 ? 'due' : 'paid';

        $saleData = [
            'business_id' => session('business.id') ?? $queue->business_id,
            'location_id' => session('business_location_id') ?? $queue->location_id,
            'sale_no' => $this->serverInvoiceNo(null),
            'invoice_no' => $payload['offline_invoice_no'] ?? $queue->offline_invoice_no,
            'customer_id' => $payload['customer_id'] ?? null,
            'customer_name' => $payload['customer_name'] ?? 'Walk-in Customer',
            'register_id' => $payload['register_id'] ?? null,
            'session_id' => $payload['session_id'] ?? null,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => $paymentStatus,
            'status' => 'final',
            'sale_date' => $payload['created_offline_at'] ?? now(),
            'note' => trim(($payload['note'] ?? '') . ' Offline sync: ' . ($payload['offline_invoice_no'] ?? $queue->offline_invoice_no)),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        foreach (['offline_client_token' => $payload['client_token'] ?? $queue->client_token, 'offline_invoice_no' => $payload['offline_invoice_no'] ?? $queue->offline_invoice_no, 'offline_device_uuid' => $payload['device_uuid'] ?? $queue->device_uuid, 'offline_synced_at' => now()] as $col => $val) {
            if (Schema::hasColumn('pos_sales', $col)) { $saleData[$col] = $val; }
        }

        $saleId = DB::table('pos_sales')->insertGetId($this->filterColumns('pos_sales', $saleData));

        foreach ($items as $line) {
            $qty = (float) ($line['quantity'] ?? $line['qty'] ?? 0);
            $price = (float) ($line['unit_price'] ?? $line['price'] ?? 0);
            $lineData = [
                'sale_id' => $saleId,
                'product_id' => $line['product_id'] ?? null,
                'product_name' => $line['product_name'] ?? $line['name'] ?? null,
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_amount' => (float) ($line['discount_amount'] ?? 0),
                'tax_amount' => (float) ($line['tax_amount'] ?? 0),
                'line_total' => (float) ($line['line_total'] ?? ($qty * $price)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('pos_sale_lines')->insert($this->filterColumns('pos_sale_lines', $lineData));

            if (Schema::hasTable('pos_products') && Schema::hasColumn('pos_products', 'stock_qty')) {
                DB::table('pos_products')->where('id', $lineData['product_id'])->decrement('stock_qty', $qty);
            }
            if (Schema::hasTable('pos_stock_movements')) {
                DB::table('pos_stock_movements')->insert($this->filterColumns('pos_stock_movements', [
                    'business_id' => session('business.id') ?? $queue->business_id,
                    'product_id' => $lineData['product_id'],
                    'type' => 'offline_sale_sync',
                    'quantity' => -1 * $qty,
                    'reference_no' => $payload['offline_invoice_no'] ?? $queue->offline_invoice_no,
                    'note' => 'Offline sale synced to server',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $payments = $payload['payments'] ?? [[
            'payment_method' => $payload['payment_method'] ?? 'cash',
            'amount' => $paid,
            'reference_no' => $payload['payment_reference'] ?? null,
        ]];
        foreach ($payments as $payment) {
            if (!Schema::hasTable('pos_payments')) { continue; }
            DB::table('pos_payments')->insert($this->filterColumns('pos_payments', [
                'business_id' => session('business.id') ?? $queue->business_id,
                'sale_id' => $saleId,
                'customer_id' => $payload['customer_id'] ?? null,
                'payment_method' => $payment['payment_method'] ?? $payment['method'] ?? 'cash',
                'amount' => (float) ($payment['amount'] ?? 0),
                'reference_no' => $payment['reference_no'] ?? null,
                'payment_date' => now(),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $saleId;
    }

    protected function filterColumns(string $table, array $data): array
    {
        if (!Schema::hasTable($table)) { return $data; }
        $columns = Schema::getColumnListing($table);
        return array_intersect_key($data, array_flip($columns));
    }

    protected function serverInvoiceNo(?int $saleId): string
    {
        if ($saleId) { return 'POS-' . str_pad((string) $saleId, 6, '0', STR_PAD_LEFT); }
        return 'POS-SYNC-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
    }

    protected function markConflict(POSSyncQueue $queue, string $type, string $message, array $payload = [], array $snapshot = []): array
    {
        $queue->status = 'conflict';
        $queue->last_error = $message;
        $queue->failed_at = now();
        $queue->save();

        if (Schema::hasTable('pos_offline_sync_conflicts')) {
            POSSyncConflict::firstOrCreate(
                ['queue_id' => $queue->id, 'conflict_type' => $type],
                [
                    'business_id' => $queue->business_id,
                    'device_uuid' => $queue->device_uuid,
                    'offline_invoice_no' => $queue->offline_invoice_no,
                    'conflict_message' => $message,
                    'payload' => $payload,
                    'server_snapshot' => $snapshot,
                    'resolution_status' => 'open',
                ]
            );
        }

        return ['queue_id' => $queue->id, 'status' => 'conflict', 'type' => $type, 'message' => $message];
    }


    public function preflightOfflinePayload(array $payload): array
    {
        $warnings = [];
        $conflicts = [];

        $items = $payload['items'] ?? [];
        if (is_array($items) && Schema::hasTable('pos_products')) {
            foreach ($items as $line) {
                $productId = $line['product_id'] ?? null;
                $qty = (float) ($line['quantity'] ?? $line['qty'] ?? 0);
                if (!$productId || $qty <= 0) { continue; }
                $product = DB::table('pos_products')->where('id', $productId)->first();
                if (!$product) {
                    $conflicts[] = ['type' => 'missing_product', 'product_id' => $productId, 'message' => 'Product missing on server.'];
                    continue;
                }
                if (property_exists($product, 'stock_qty') && (float) $product->stock_qty < $qty) {
                    $conflicts[] = ['type' => 'stock_warning', 'product_id' => $productId, 'server_stock' => (float) $product->stock_qty, 'offline_qty' => $qty, 'message' => 'Server stock is already lower than offline quantity.'];
                }
            }
        }

        if (!empty($payload['credit_sale']) && !empty($payload['customer_id'])) {
            $credit = $this->validateCustomerCredit($payload);
            if (!$credit['ok']) {
                $conflicts[] = ['type' => $credit['type'] ?? 'credit_warning', 'message' => $credit['message'] ?? 'Customer credit validation warning.', 'snapshot' => $credit['snapshot'] ?? []];
            }
        }

        if (!empty($payload['offline_invoice_no']) && Schema::hasTable('pos_offline_sync_queue')) {
            $existing = DB::table('pos_offline_sync_queue')
                ->where('offline_invoice_no', $payload['offline_invoice_no'])
                ->where('client_token', '<>', $payload['client_token'] ?? '')
                ->first();
            if ($existing) {
                $conflicts[] = ['type' => 'invoice_duplicate_warning', 'message' => 'Another offline queue item already uses this offline invoice number.'];
            }
        }

        return ['ok' => count($conflicts) === 0, 'warnings' => $warnings, 'conflicts' => $conflicts];
    }



    public function conflictSummary(): array
    {
        if (!Schema::hasTable('pos_offline_sync_conflicts')) {
            return ['open' => 0, 'resolved_today' => 0, 'failed' => 0, 'retryable' => 0, 'by_type' => []];
        }

        $open = (int) DB::table('pos_offline_sync_conflicts')->where('resolution_status', 'open')->count();
        $resolvedToday = (int) DB::table('pos_offline_sync_conflicts')->whereDate('resolved_at', now()->toDateString())->count();
        $failed = Schema::hasTable('pos_offline_sync_queue') ? (int) DB::table('pos_offline_sync_queue')->where('status', 'failed')->count() : 0;
        $retryable = Schema::hasTable('pos_offline_sync_queue') ? (int) DB::table('pos_offline_sync_queue')->whereIn('status', ['failed', 'conflict'])->count() : 0;
        $byType = DB::table('pos_offline_sync_conflicts')
            ->select('conflict_type', DB::raw('COUNT(*) as total'))
            ->where('resolution_status', 'open')
            ->groupBy('conflict_type')
            ->orderBy('total', 'desc')
            ->pluck('total', 'conflict_type')
            ->toArray();

        return compact('open', 'resolvedToday', 'failed', 'retryable') + ['by_type' => $byType];
    }

    public function resolutionOptions(): array
    {
        return [
            'retry_after_cache_refresh' => 'Retry after refreshing server cache/stock/customer snapshot',
            'accept_server_version' => 'Accept server version and keep the transaction blocked from posting',
            'manager_approve_negative_stock' => 'Manager approve posting with negative stock / adjustment review',
            'manager_approve_credit_over_limit' => 'Manager approve credit sale above limit',
            'convert_to_cash_or_card' => 'Convert credit sale to cash/card before retry',
            'cancel_offline_transaction' => 'Cancel offline transaction and keep audit trail',
            'manual_adjustment_created' => 'Manual stock/customer/register adjustment already created',
        ];
    }

    public function retryConflict(int $conflictId): array
    {
        if (!Schema::hasTable('pos_offline_sync_conflicts') || !Schema::hasTable('pos_offline_sync_queue')) {
            return ['ok' => false, 'message' => 'Sync conflict/queue tables are missing.'];
        }

        $conflict = POSSyncConflict::find($conflictId);
        if (!$conflict) {
            return ['ok' => false, 'message' => 'Conflict not found.'];
        }

        $queue = POSSyncQueue::find($conflict->queue_id);
        if (!$queue) {
            return ['ok' => false, 'message' => 'Linked queue item not found.'];
        }

        $queue->status = 'pending';
        $queue->last_error = null;
        $queue->failed_at = null;
        if (Schema::hasColumn('pos_offline_sync_queue', 'locked_at')) { $queue->locked_at = null; }
        if (Schema::hasColumn('pos_offline_sync_queue', 'locked_by')) { $queue->locked_by = null; }
        $queue->save();

        $conflict->resolution_note = trim((string) $conflict->resolution_note . "\nRetried by manager on " . now()->toDateTimeString());
        $conflict->save();

        return $this->syncPending(1);
    }

    public function retryAllConflicts(int $limit = 50): array
    {
        if (!Schema::hasTable('pos_offline_sync_queue')) {
            return ['ok' => false, 'message' => 'Sync queue table is missing.', 'processed' => 0];
        }

        $update = [
            'status' => 'pending',
            'last_error' => null,
            'failed_at' => null,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('pos_offline_sync_queue', 'locked_at')) { $update['locked_at'] = null; }
        if (Schema::hasColumn('pos_offline_sync_queue', 'locked_by')) { $update['locked_by'] = null; }

        POSSyncQueue::whereIn('status', ['failed', 'conflict'])
            ->orderBy('id')
            ->limit($limit)
            ->update($update);

        return $this->syncPending($limit);
    }

    public function resolveConflict(int $conflictId, string $action, string $note = ''): array
    {
        if (!Schema::hasTable('pos_offline_sync_conflicts')) {
            return ['ok' => false, 'message' => 'Sync conflict table is missing.'];
        }

        $allowed = array_keys($this->resolutionOptions());
        if (!in_array($action, $allowed, true)) {
            return ['ok' => false, 'message' => 'Unsupported resolution action.'];
        }

        $conflict = POSSyncConflict::find($conflictId);
        if (!$conflict) {
            return ['ok' => false, 'message' => 'Conflict not found.'];
        }

        return DB::transaction(function () use ($conflict, $action, $note) {
            $queue = $conflict->queue_id ? POSSyncQueue::find($conflict->queue_id) : null;

            if ($queue && in_array($action, ['manager_approve_negative_stock', 'manager_approve_credit_over_limit', 'convert_to_cash_or_card', 'retry_after_cache_refresh'], true)) {
                $payload = is_array($queue->payload) ? $queue->payload : (json_decode((string) $queue->payload, true) ?: []);
                $payload['_manager_resolution'] = [
                    'action' => $action,
                    'note' => $note,
                    'resolved_by' => auth()->id(),
                    'resolved_at' => now()->toDateTimeString(),
                ];
                if ($action === 'manager_approve_negative_stock') {
                    $payload['allow_negative_stock_by_manager'] = true;
                }
                if ($action === 'manager_approve_credit_over_limit') {
                    $payload['allow_credit_over_limit_by_manager'] = true;
                }
                if ($action === 'convert_to_cash_or_card') {
                    $payload['credit_sale'] = false;
                    $payload['payment_method'] = $payload['payment_method_override'] ?? 'cash';
                }
                $queue->payload = $payload;
                $queue->status = 'pending';
                $queue->last_error = null;
                $queue->failed_at = null;
                $queue->save();
            }

            if ($queue && in_array($action, ['cancel_offline_transaction', 'accept_server_version', 'manual_adjustment_created'], true)) {
                $queue->status = $action === 'cancel_offline_transaction' ? 'failed' : 'synced';
                $queue->server_response = array_merge((array) ($queue->server_response ?? []), [
                    'manual_resolution' => $action,
                    'resolution_note' => $note,
                    'resolved_by' => auth()->id(),
                    'resolved_at' => now()->toDateTimeString(),
                ]);
                $queue->last_error = $action === 'cancel_offline_transaction' ? 'Cancelled by manager during offline conflict resolution.' : null;
                $queue->synced_at = $action === 'cancel_offline_transaction' ? $queue->synced_at : now();
                $queue->save();
            }

            $conflict->resolution_status = 'resolved';
            $conflict->resolution_note = trim($note . "\nAction: " . $action);
            $conflict->resolved_by = auth()->id();
            $conflict->resolved_at = now();
            $conflict->save();

            return ['ok' => true, 'message' => 'Conflict resolved.', 'action' => $action, 'conflict_id' => $conflict->id];
        });
    }

    public function validatePayload(array $payload): array
    {
        $warnings = [];
        if (empty($payload['items'])) {
            $warnings[] = 'No sale/return items found.';
        }
        if (($payload['transaction_type'] ?? 'sale') === 'return' && empty($payload['original_invoice_no'])) {
            $warnings[] = 'Return is missing original invoice reference.';
        }
        if (($payload['payment_status'] ?? '') === 'partial') {
            $warnings[] = 'Partial payment requires manager review if sync is interrupted.';
        }
        if (!empty($payload['customer_id']) && !empty($payload['credit_sale'])) {
            $warnings[] = 'Credit sale must be checked against latest customer balance during server sync.';
        }
        return $warnings;
    }


    public function backgroundEngineStatus(): array
    {
        $dashboard = $this->dashboard();
        $pending = $dashboard['queue']['pending'] ?? 0;
        $failed = $dashboard['queue']['failed'] ?? 0;
        $conflict = $dashboard['queue']['conflict'] ?? 0;
        $synced = $dashboard['queue']['synced'] ?? 0;

        $oldestPending = null;
        if (Schema::hasTable('pos_offline_sync_queue')) {
            $oldestPending = DB::table('pos_offline_sync_queue')
                ->whereIn('status', ['pending', 'failed', 'conflict'])
                ->orderBy('created_at')
                ->value('created_at');
        }

        $heartbeat = null;
        if (Schema::hasTable('pos_devices')) {
            $heartbeat = DB::table('pos_devices')
                ->where('device_uuid', $this->deviceUuid())
                ->first();
        }

        return $dashboard + [
            'engine' => [
                'pending' => (int) $pending,
                'failed' => (int) $failed,
                'conflict' => (int) $conflict,
                'synced' => (int) $synced,
                'oldest_pending_at' => $oldestPending,
                'recommended_batch_size' => $pending > 500 ? 100 : 50,
                'background_worker' => 'enabled_by_browser',
                'resume_mode' => 'idempotent_queue_item',
                'network_quality' => $heartbeat->network_quality ?? 'unknown',
                'last_heartbeat_at' => $heartbeat->last_seen_at ?? null,
            ],
        ];
    }

    public function processBackgroundBatch(int $limit = 50): array
    {
        if (!Schema::hasTable('pos_offline_sync_queue')) {
            return ['ok' => false, 'message' => 'Offline sync queue table missing.', 'processed' => 0, 'results' => []];
        }

        // S383 processes safe events in dependency order. Later stages can expand transaction types.
        $dependencyOrder = ['register_open', 'shift_start', 'sale', 'payment', 'return', 'cash_movement', 'register_close', 'shift_close'];
        $processed = [];
        $remaining = $limit;

        foreach ($dependencyOrder as $type) {
            if ($remaining <= 0) { break; }
            $items = POSSyncQueue::where('status', 'pending')
                ->where(function ($q) use ($type) {
                    $q->where('transaction_type', $type);
                    if ($type === 'sale') { $q->orWhereNull('transaction_type'); }
                })
                ->orderBy('id')
                ->limit($remaining)
                ->get();

            foreach ($items as $item) {
                $processed[] = $this->processQueueItem($item);
                $remaining--;
                if ($remaining <= 0) { break; }
            }
        }

        return [
            'ok' => true,
            'batch_id' => (string) \Illuminate\Support\Str::uuid(),
            'processed' => count($processed),
            'results' => $processed,
            'server_time' => now()->toDateTimeString(),
        ];
    }

    public function syncProgress(): array
    {
        if (!Schema::hasTable('pos_offline_sync_queue')) {
            return ['ok' => false, 'message' => 'Offline sync queue table missing.', 'progress' => []];
        }
        $counts = DB::table('pos_offline_sync_queue')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        $total = array_sum(array_map('intval', $counts));
        $done = (int) ($counts['synced'] ?? 0);
        return [
            'ok' => true,
            'progress' => [
                'total' => $total,
                'synced' => $done,
                'pending' => (int) ($counts['pending'] ?? 0),
                'failed' => (int) ($counts['failed'] ?? 0),
                'conflict' => (int) ($counts['conflict'] ?? 0),
                'percent' => $total > 0 ? round(($done / $total) * 100, 2) : 100,
                'last_synced_at' => DB::table('pos_offline_sync_queue')->whereNotNull('synced_at')->max('synced_at'),
            ],
            'server_time' => now()->toDateTimeString(),
        ];
    }

    public function recordHeartbeat(array $payload): array
    {
        if (!Schema::hasTable('pos_devices')) {
            return ['ok' => false, 'message' => 'pos_devices table missing.'];
        }
        $deviceUuid = $payload['device_uuid'] ?? $this->deviceUuid();
        $data = [
            'device_uuid' => $deviceUuid,
            'terminal_code' => $payload['terminal_code'] ?? null,
            'business_id' => session('business.id') ?? null,
            'location_id' => session('business_location_id') ?? null,
            'last_seen_at' => now(),
            'status' => !empty($payload['online']) ? 'online' : 'offline_seen',
            'network_quality' => $payload['network_quality'] ?? null,
            'queue_size' => (int) ($payload['queue_size'] ?? 0),
            'browser_info' => substr((string) ($payload['browser_info'] ?? request()->userAgent()), 0, 500),
            'updated_at' => now(),
        ];
        DB::table('pos_devices')->updateOrInsert(['device_uuid' => $deviceUuid], $data + ['created_at' => now()]);
        return ['ok' => true, 'device_uuid' => $deviceUuid, 'server_time' => now()->toDateTimeString()];
    }

    public function recordNetworkSample(array $payload): array
    {
        if (!Schema::hasTable('pos_sync_network_samples')) {
            return ['ok' => false, 'message' => 'pos_sync_network_samples table missing. Install S383 SQL.'];
        }
        DB::table('pos_sync_network_samples')->insert([
            'business_id' => session('business.id') ?? null,
            'location_id' => session('business_location_id') ?? null,
            'device_uuid' => $payload['device_uuid'] ?? $this->deviceUuid(),
            'latency_ms' => (int) ($payload['latency_ms'] ?? 0),
            'online' => !empty($payload['online']) ? 1 : 0,
            'quality' => $payload['quality'] ?? null,
            'sampled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return ['ok' => true, 'server_time' => now()->toDateTimeString()];
    }

    public function ackBatch(array $payload): array
    {
        return [
            'ok' => true,
            'batch_id' => $payload['batch_id'] ?? null,
            'message' => 'Batch acknowledgement recorded. Queue rows already carry final sync/conflict state.',
            'server_time' => now()->toDateTimeString(),
        ];
    }


    public function monitoringCenterStatus(): array
    {
        $dashboard = $this->dashboard();
        $devices = $this->deviceList();

        $online = 0;
        $offline = 0;
        $weak = 0;
        foreach ($devices['items'] ?? [] as $device) {
            $lastSeen = !empty($device->last_seen_at) ? strtotime((string) $device->last_seen_at) : 0;
            $isOnline = $lastSeen && (time() - $lastSeen) <= 180 && ($device->status ?? '') !== 'blocked';
            if ($isOnline) { $online++; } else { $offline++; }
            if (in_array(strtolower((string) ($device->network_quality ?? '')), ['poor', 'weak', 'offline'], true)) { $weak++; }
        }

        $queueByDevice = [];
        if (Schema::hasTable('pos_offline_sync_queue')) {
            $queueByDevice = DB::table('pos_offline_sync_queue')
                ->select('device_uuid', 'status', DB::raw('COUNT(*) as total'))
                ->groupBy('device_uuid', 'status')
                ->orderBy('device_uuid')
                ->get()
                ->groupBy('device_uuid')
                ->map(function ($rows) {
                    $out = ['pending' => 0, 'failed' => 0, 'conflict' => 0, 'synced' => 0];
                    foreach ($rows as $row) { $out[$row->status] = (int) $row->total; }
                    return $out;
                })
                ->toArray();
        }

        $avgLatency = null;
        if (Schema::hasTable('pos_sync_network_samples')) {
            $avgLatency = DB::table('pos_sync_network_samples')
                ->where('sampled_at', '>=', now()->subHours(6))
                ->avg('latency_ms');
        }

        return $dashboard + [
            'monitor' => [
                'online_devices' => $online,
                'offline_devices' => $offline,
                'weak_network_devices' => $weak,
                'total_devices' => count($devices['items'] ?? []),
                'avg_latency_ms_6h' => $avgLatency ? round((float) $avgLatency, 2) : null,
                'queue_by_device' => $queueByDevice,
                'last_checked_at' => now()->toDateTimeString(),
            ],
            'devices' => $devices['items'] ?? [],
        ];
    }

    public function registerDevice(array $payload): array
    {
        if (!Schema::hasTable('pos_devices')) {
            return ['ok' => false, 'message' => 'pos_devices table missing.'];
        }

        $deviceUuid = (string) ($payload['device_uuid'] ?? $this->deviceUuid());
        $trustStatus = 'trusted';
        $existing = DB::table('pos_devices')->where('device_uuid', $deviceUuid)->first();
        if ($existing && property_exists($existing, 'trust_status') && $existing->trust_status === 'blocked') {
            return ['ok' => false, 'message' => 'This POS terminal is blocked from synchronization.', 'device_uuid' => $deviceUuid];
        }

        $data = [
            'device_uuid' => $deviceUuid,
            'terminal_code' => $payload['terminal_code'] ?? ($existing->terminal_code ?? null),
            'business_id' => session('business.id') ?? ($existing->business_id ?? null),
            'location_id' => session('business_location_id') ?? ($existing->location_id ?? null),
            'register_id' => $payload['register_id'] ?? ($existing->register_id ?? null),
            'cashier_id' => auth()->id(),
            'pos_version' => $payload['pos_version'] ?? 's384',
            'last_seen_at' => now(),
            'status' => 'online',
            'network_quality' => $payload['network_quality'] ?? ($existing->network_quality ?? null),
            'queue_size' => (int) ($payload['queue_size'] ?? 0),
            'browser_info' => substr((string) ($payload['browser_info'] ?? request()->userAgent()), 0, 500),
            'trust_status' => $trustStatus,
            'updated_at' => now(),
        ];
        DB::table('pos_devices')->updateOrInsert(['device_uuid' => $deviceUuid], $this->filterColumns('pos_devices', $data + ['created_at' => now()]));

        return ['ok' => true, 'device_uuid' => $deviceUuid, 'trust_status' => $trustStatus, 'server_time' => now()->toDateTimeString()];
    }

    public function deviceList(): array
    {
        if (!Schema::hasTable('pos_devices')) {
            return ['ok' => false, 'items' => [], 'message' => 'pos_devices table missing.'];
        }

        $items = DB::table('pos_devices')
            ->orderByRaw('CASE WHEN last_seen_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('last_seen_at', 'desc')
            ->limit(200)
            ->get();

        return ['ok' => true, 'items' => $items, 'count' => $items->count()];
    }

    public function setDeviceTrust(string $deviceUuid, string $status): array
    {
        if (!Schema::hasTable('pos_devices')) {
            return ['ok' => false, 'message' => 'pos_devices table missing.'];
        }
        if (!in_array($status, ['trusted', 'blocked'], true)) {
            return ['ok' => false, 'message' => 'Invalid device trust status.'];
        }

        $update = ['status' => $status === 'blocked' ? 'blocked' : 'online', 'updated_at' => now()];
        if (Schema::hasColumn('pos_devices', 'trust_status')) { $update['trust_status'] = $status; }
        if (Schema::hasColumn('pos_devices', 'trusted_by')) { $update['trusted_by'] = auth()->id(); }
        if (Schema::hasColumn('pos_devices', 'trusted_at')) { $update['trusted_at'] = now(); }

        DB::table('pos_devices')->where('device_uuid', $deviceUuid)->update($update);

        return ['ok' => true, 'device_uuid' => $deviceUuid, 'trust_status' => $status, 'server_time' => now()->toDateTimeString()];
    }

}
