<?php
namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\POS\Services\POSOfflineSyncService;

class OfflineSyncController extends Controller
{
    protected POSOfflineSyncService $service;

    public function __construct(POSOfflineSyncService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $status = $this->service->dashboard();
        $riskControls = [
            ['risk' => 'Duplicate sync after refresh', 'control' => 'client_token is unique and idempotent on server.'],
            ['risk' => 'Duplicate offline invoice numbers', 'control' => 'invoice format uses terminal/device/date/local sequence.'],
            ['risk' => 'Stock overselling', 'control' => 'server validates stock again during final sync posting.'],
            ['risk' => 'Credit limit mismatch', 'control' => 'server checks latest Customers module balance before confirming credit sale.'],
            ['risk' => 'Partial payment sync failure', 'control' => 'partial payments are flagged for review until all lines/payments are posted.'],
            ['risk' => 'Return before original sale synced', 'control' => 'return payload must reference synced sale or goes to conflict queue.'],
            ['risk' => 'Register closing while offline', 'control' => 'offline close is marked pending and requires server reconciliation.'],
            ['risk' => 'Customer ledger mismatch', 'control' => 'ledger posting happens server-side after successful sale sync only.'],
        ];

        return view('pos::offline_sync.index', compact('status', 'riskControls'));
    }

    public function manifest()
    {
        return response()->json([
            'ok' => true,
            'device_uuid' => $this->service->deviceUuid(),
            'server_time' => now()->toDateTimeString(),
            'sync_tables_ready' => $this->service->dashboard()['tables'],
            'cache_sets' => [
                'products' => '/pos-module/offline-sync/cache/products',
                'customers' => '/pos-module/offline-sync/cache/customers',
                'settings' => '/pos-module/offline-sync/cache/settings',
                'stock' => '/pos-module/offline-sync/cache/stock',
                'all' => '/pos-module/offline-sync/cache/all',
            ],
        ]);
    }

    public function queue(Request $request)
    {
        $payload = $request->all();
        $warnings = $this->service->validatePayload($payload);
        $result = $this->service->acceptQueueItem($payload);
        $result['warnings'] = $warnings;
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function pending()
    {
        if (!Schema::hasTable('pos_offline_sync_queue')) {
            return response()->json(['ok' => false, 'message' => 'Offline sync queue table missing.', 'items' => []], 200);
        }

        $items = DB::table('pos_offline_sync_queue')
            ->whereIn('status', ['pending', 'failed', 'conflict'])
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return response()->json(['ok' => true, 'items' => $items]);
    }



    public function syncNow(Request $request)
    {
        $limit = (int) $request->input('limit', 25);
        $limit = max(1, min($limit, 100));
        $result = $this->service->syncPending($limit);
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function offlineSales()
    {
        if (!Schema::hasTable('pos_offline_sync_queue')) {
            return response()->json(['ok' => false, 'message' => 'Offline sync queue table missing.', 'items' => []], 200);
        }

        $items = DB::table('pos_offline_sync_queue')
            ->where('transaction_type', 'sale')
            ->orderBy('id', 'desc')
            ->limit(150)
            ->get();

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function cacheProducts()
    {
        $table = Schema::hasTable('pos_products') ? 'pos_products' : (Schema::hasTable('products') ? 'products' : null);
        if (!$table) {
            return response()->json(['ok' => false, 'items' => []]);
        }

        $columns = Schema::getColumnListing($table);
        $select = ['id'];
        foreach (['name', 'sku', 'barcode', 'selling_price', 'default_sell_price', 'price', 'stock_qty', 'enable_stock', 'category_id', 'brand_id', 'updated_at'] as $col) {
            if (in_array($col, $columns, true)) { $select[] = $col; }
        }
        $items = DB::table($table)->select($select)->orderBy('name')->limit(10000)->get()->map(function ($row) {
            $row = (array) $row;
            $row['offline_price'] = $row['selling_price'] ?? $row['default_sell_price'] ?? $row['price'] ?? 0;
            $row['offline_stock'] = $row['stock_qty'] ?? null;
            return $row;
        });
        return response()->json(['ok' => true, 'items' => $items, 'version_hash' => sha1($items->toJson()), 'cached_at' => now()->toDateTimeString()]);
    }

    public function cacheCustomers()
    {
        $table = Schema::hasTable('customers') ? 'customers' : (Schema::hasTable('pos_customers') ? 'pos_customers' : null);
        if (!$table) {
            return response()->json(['ok' => false, 'items' => []]);
        }

        $columns = Schema::getColumnListing($table);
        $select = ['id'];
        foreach (['name', 'mobile', 'phone', 'email', 'credit_limit', 'balance'] as $col) {
            if (in_array($col, $columns, true)) { $select[] = $col; }
        }
        $items = DB::table($table)->select($select)->orderBy('id', 'desc')->limit(10000)->get();
        return response()->json(['ok' => true, 'items' => $items, 'version_hash' => sha1($items->toJson()), 'cached_at' => now()->toDateTimeString()]);
    }

    public function cacheSettings()
    {
        return response()->json([
            'ok' => true,
            'settings' => [
                'offline_invoice_prefix' => 'OFF',
                'allow_offline_credit_sale' => false,
                'allow_offline_return' => false,
                'max_offline_sale_amount' => 0,
                'require_manager_review_on_sync_conflict' => true,
                'offline_stock_mode' => 'snapshot_then_server_validate',
                'offline_customer_mode' => 'summary_cache_then_server_validate',
                'offline_return_mode' => 'manager_review_required',
            ],
            'cached_at' => now()->toDateTimeString(),
        ]);
    }

    public function cacheStock()
    {
        if (!Schema::hasTable('pos_products')) {
            return response()->json(['ok' => false, 'items' => [], 'message' => 'pos_products table missing.']);
        }

        $columns = Schema::getColumnListing('pos_products');
        $select = ['id'];
        foreach (['name', 'sku', 'barcode', 'stock_qty', 'updated_at'] as $col) {
            if (in_array($col, $columns, true)) { $select[] = $col; }
        }
        $items = DB::table('pos_products')->select($select)->orderBy('id')->limit(10000)->get();
        return response()->json(['ok' => true, 'items' => $items, 'version_hash' => sha1($items->toJson()), 'cached_at' => now()->toDateTimeString()]);
    }

    public function cacheAll()
    {
        return response()->json([
            'ok' => true,
            'server_time' => now()->toDateTimeString(),
            'products_url' => '/pos-module/offline-sync/cache/products',
            'customers_url' => '/pos-module/offline-sync/cache/customers',
            'settings_url' => '/pos-module/offline-sync/cache/settings',
            'stock_url' => '/pos-module/offline-sync/cache/stock',
            'message' => 'Use the individual endpoints to refresh each local IndexedDB store. This avoids one huge browser response on large tenants.',
        ]);
    }

    public function preflight(Request $request)
    {
        $payload = $request->all();
        $warnings = $this->service->validatePayload($payload);
        $serverValidation = method_exists($this->service, 'preflightOfflinePayload')
            ? $this->service->preflightOfflinePayload($payload)
            : ['ok' => true, 'warnings' => []];
        return response()->json([
            'ok' => $serverValidation['ok'] ?? true,
            'warnings' => array_values(array_merge($warnings, $serverValidation['warnings'] ?? [])),
            'conflicts' => $serverValidation['conflicts'] ?? [],
            'server_time' => now()->toDateTimeString(),
        ], ($serverValidation['ok'] ?? true) ? 200 : 422);
    }

    public function conflicts()
    {
        if (!Schema::hasTable('pos_offline_sync_conflicts')) {
            return response()->json(['ok' => false, 'items' => [], 'message' => 'Conflict table missing.']);
        }
        $items = DB::table('pos_offline_sync_conflicts')
            ->whereNull('resolved_at')
            ->orderBy('id', 'desc')
            ->limit(150)
            ->get();
        return response()->json(['ok' => true, 'items' => $items, 'count' => $items->count()]);
    }


    public function conflictManager()
    {
        $summary = method_exists($this->service, 'conflictSummary')
            ? $this->service->conflictSummary()
            : ['open' => 0, 'resolved_today' => 0, 'failed' => 0, 'retryable' => 0, 'by_type' => []];

        $items = [];
        if (Schema::hasTable('pos_offline_sync_conflicts')) {
            $items = DB::table('pos_offline_sync_conflicts')
                ->leftJoin('pos_offline_sync_queue', 'pos_offline_sync_conflicts.queue_id', '=', 'pos_offline_sync_queue.id')
                ->select('pos_offline_sync_conflicts.*', 'pos_offline_sync_queue.status as queue_status', 'pos_offline_sync_queue.attempt_count', 'pos_offline_sync_queue.last_error', 'pos_offline_sync_queue.transaction_type')
                ->orderByRaw("CASE WHEN pos_offline_sync_conflicts.resolution_status = 'open' THEN 0 ELSE 1 END")
                ->orderBy('pos_offline_sync_conflicts.id', 'desc')
                ->limit(200)
                ->get();
        }

        $resolutionOptions = $this->service->resolutionOptions();

        return view('pos::offline_sync.conflict_manager', compact('summary', 'items', 'resolutionOptions'));
    }

    public function retryConflict($id)
    {
        $result = $this->service->retryConflict((int) $id);
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function retryAllConflicts(Request $request)
    {
        $limit = (int) $request->input('limit', 50);
        $result = $this->service->retryAllConflicts(max(1, min($limit, 200)));
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function resolveConflict(Request $request, $id)
    {
        $request->validate([
            'resolution_action' => 'required|string|max:80',
            'resolution_note' => 'nullable|string|max:2000',
        ]);
        $result = $this->service->resolveConflict((int) $id, $request->input('resolution_action'), (string) $request->input('resolution_note'));
        return response()->json($result, $result['ok'] ? 200 : 422);
    }



    /**
     * S383 - Background synchronization control center.
     */
    public function backgroundEngine()
    {
        $status = method_exists($this->service, 'backgroundEngineStatus')
            ? $this->service->backgroundEngineStatus()
            : $this->service->dashboard();

        $engineControls = [
            ['name' => 'Automatic reconnect sync', 'status' => 'Ready', 'note' => 'Browser worker wakes up when connection returns and sends pending queue in safe batches.'],
            ['name' => 'Dependency-aware ordering', 'status' => 'Ready', 'note' => 'Register/shift/sale/payment/return events are processed in safe priority order.'],
            ['name' => 'Resume interrupted sync', 'status' => 'Ready', 'note' => 'Each queue item keeps attempts, lock and batch metadata so retry can continue instead of duplicating.'],
            ['name' => 'Device heartbeat', 'status' => 'Ready', 'note' => 'Terminal sends last seen, queue size, online status and network quality.'],
            ['name' => 'Network quality monitor', 'status' => 'Ready', 'note' => 'Latency samples are stored to help managers see weak terminals/connections.'],
            ['name' => 'Payload encryption foundation', 'status' => 'Foundation', 'note' => 'Transport remains HTTPS; payload hash/envelope fields are prepared for stronger encryption in the next stage.'],
        ];

        return view('pos::offline_sync.background_engine', compact('status', 'engineControls'));
    }

    public function heartbeat(Request $request)
    {
        $result = method_exists($this->service, 'recordHeartbeat')
            ? $this->service->recordHeartbeat($request->all())
            : ['ok' => true, 'server_time' => now()->toDateTimeString()];
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function batchNext(Request $request)
    {
        $limit = (int) $request->input('limit', 50);
        $limit = max(1, min($limit, 200));
        $result = method_exists($this->service, 'processBackgroundBatch')
            ? $this->service->processBackgroundBatch($limit)
            : $this->service->syncPending($limit);
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function ackBatch(Request $request)
    {
        $result = method_exists($this->service, 'ackBatch')
            ? $this->service->ackBatch($request->all())
            : ['ok' => true, 'message' => 'Batch acknowledged.', 'server_time' => now()->toDateTimeString()];
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function progress()
    {
        $result = method_exists($this->service, 'syncProgress')
            ? $this->service->syncProgress()
            : ['ok' => true, 'progress' => []];
        return response()->json($result);
    }

    public function networkSample(Request $request)
    {
        $result = method_exists($this->service, 'recordNetworkSample')
            ? $this->service->recordNetworkSample($request->all())
            : ['ok' => true, 'server_time' => now()->toDateTimeString()];
        return response()->json($result, $result['ok'] ? 200 : 422);
    }


    /**
     * S384 - Enterprise Monitoring & Multi-Terminal Synchronization Center.
     */
    public function monitoringCenter()
    {
        $status = method_exists($this->service, 'monitoringCenterStatus')
            ? $this->service->monitoringCenterStatus()
            : $this->service->dashboard();

        $controlBlocks = [
            ['title' => 'Terminal Health', 'note' => 'Tracks online/offline terminals, heartbeat age, queue size and network quality.'],
            ['title' => 'Multi-Terminal Coordination', 'note' => 'Links device UUID with business, branch/location, register and cashier for safe sync monitoring.'],
            ['title' => 'Branch-Aware Sync', 'note' => 'Keeps queue and device statistics isolated by business/location to protect tenant and branch data.'],
            ['title' => 'Conflict Reduction', 'note' => 'Warns managers about stale stock, large pending queues and credit/register risks before sync failure.'],
            ['title' => 'Security Foundation', 'note' => 'Trusted/blocked device status, request hash fields and replay protection foundation.'],
        ];

        return view('pos::offline_sync.monitoring_center', compact('status', 'controlBlocks'));
    }

    public function monitoringStatus()
    {
        $result = method_exists($this->service, 'monitoringCenterStatus')
            ? $this->service->monitoringCenterStatus()
            : $this->service->dashboard();
        return response()->json(['ok' => true, 'status' => $result, 'server_time' => now()->toDateTimeString()]);
    }

    public function deviceRegister(Request $request)
    {
        $result = method_exists($this->service, 'registerDevice')
            ? $this->service->registerDevice($request->all())
            : ['ok' => false, 'message' => 'Device registration service unavailable.'];
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function devices()
    {
        $result = method_exists($this->service, 'deviceList')
            ? $this->service->deviceList()
            : ['ok' => true, 'items' => []];
        return response()->json($result);
    }

    public function trustDevice($deviceUuid)
    {
        $result = method_exists($this->service, 'setDeviceTrust')
            ? $this->service->setDeviceTrust((string) $deviceUuid, 'trusted')
            : ['ok' => false, 'message' => 'Device trust service unavailable.'];
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function blockDevice($deviceUuid)
    {
        $result = method_exists($this->service, 'setDeviceTrust')
            ? $this->service->setDeviceTrust((string) $deviceUuid, 'blocked')
            : ['ok' => false, 'message' => 'Device trust service unavailable.'];
        return response()->json($result, $result['ok'] ? 200 : 422);
    }

}
