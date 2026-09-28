<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerPortalDeliveryController extends CustomerPortalController
{
    public function index(Request $request)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $customerId = (int) $customer->id;

        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => trim((string) $request->get('search')),
        ];

        $deliveries = $this->deliveryRows($businessId, $customerId, $filters);
        $summary = $this->summaryCards($deliveries);

        return view('customers::portal.deliveries', compact('customer', 'deliveries', 'summary', 'filters'));
    }

    public function show(Request $request, $id)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $customerId = (int) $customer->id;
        $delivery = $this->findDelivery($businessId, $customerId, $id);

        if (empty($delivery)) {
            abort(404);
        }

        $items = $this->deliveryItems($businessId, $customerId, $delivery);

        return view('customers::portal.delivery_show', compact('customer', 'delivery', 'items'));
    }

    public function print(Request $request, $id)
    {
        return $this->show($request, $id);
    }


    /**
     * CUS-032: Live delivery tracking list for Distribution Dealers.
     * This is intentionally read-only and customer-scoped.
     */
    public function tracking(Request $request)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $customerId = (int) $customer->id;
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => trim((string) $request->get('search')),
        ];

        $deliveries = $this->deliveryRows($businessId, $customerId, $filters)
            ->map(function ($delivery) use ($businessId, $customerId) {
                $delivery->timeline = $this->deliveryTimeline($businessId, $customerId, $delivery);
                $delivery->location = $this->deliveryLocation($businessId, $customerId, $delivery);
                return $delivery;
            });

        $summary = $this->summaryCards($deliveries);

        return view('customers::portal.live_tracking', compact('customer', 'deliveries', 'summary', 'filters'));
    }

    /**
     * CUS-032: Delivery tracking detail page.
     */
    public function track(Request $request, $id)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }

        $businessId = $this->businessId($request);
        $customerId = (int) $customer->id;
        $delivery = $this->findDelivery($businessId, $customerId, $id);

        if (empty($delivery)) {
            abort(404);
        }

        $items = $this->deliveryItems($businessId, $customerId, $delivery);
        $timeline = $this->deliveryTimeline($businessId, $customerId, $delivery);
        $location = $this->deliveryLocation($businessId, $customerId, $delivery);

        return view('customers::portal.delivery_track', compact('customer', 'delivery', 'items', 'timeline', 'location'));
    }

    /**
     * Build a delivery timeline from optional tracking tables, with a safe fallback
     * from the delivery row itself. This prevents a blank screen where GPS/timeline
     * tables are not yet created.
     */
    protected function deliveryTimeline(int $businessId, int $customerId, $delivery)
    {
        foreach (['customer_portal_delivery_tracking', 'distribution_delivery_tracking', 'delivery_tracking'] as $table) {
            if (Schema::hasTable($table)) {
                $query = DB::table($table)->where('business_id', $businessId);

                if (Schema::hasColumn($table, 'contact_id')) {
                    $query->where('contact_id', $customerId);
                }
                if (Schema::hasColumn($table, 'delivery_id')) {
                    $query->where('delivery_id', (int) $delivery->id);
                } elseif (Schema::hasColumn($table, 'delivery_no')) {
                    $query->where('delivery_no', (string) $delivery->delivery_no);
                }

                $rows = $query->orderBy('created_at')->get();
                if ($rows->count() > 0) {
                    return $rows->map(function ($row) {
                        return (object) [
                            'status' => $this->normalizeStatus($row->status ?? $row->event ?? 'pending'),
                            'label' => ucwords(str_replace('_', ' ', $row->status ?? $row->event ?? 'Pending')),
                            'datetime' => $row->event_datetime ?? $row->created_at ?? null,
                            'remarks' => $row->remarks ?? $row->note ?? null,
                        ];
                    })->values();
                }
            }
        }

        $steps = ['approved', 'loaded', 'dispatched', 'in_transit', 'arrived', 'delivered'];
        $current = $this->normalizeStatus($delivery->status ?? 'pending');
        $currentIndex = array_search($current, $steps, true);
        if ($current === 'pending') {
            $currentIndex = -1;
        }
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        return collect(array_merge(['pending'], $steps))->map(function ($status, $index) use ($delivery, $currentIndex, $current) {
            $done = $status === 'pending' || $index <= ($currentIndex + 1) || $status === $current;
            return (object) [
                'status' => $status,
                'label' => ucwords(str_replace('_', ' ', $status)),
                'datetime' => $done ? ($delivery->dispatch_date ?? null) : null,
                'remarks' => $done ? 'Status available from delivery record.' : 'Pending update.',
            ];
        });
    }

    /**
     * Return latest vehicle/location information if optional GPS tables exist.
     */
    protected function deliveryLocation(int $businessId, int $customerId, $delivery)
    {
        foreach (['customer_portal_vehicle_locations', 'distribution_vehicle_locations', 'vehicle_locations'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)->where('business_id', $businessId);

            if (Schema::hasColumn($table, 'contact_id')) {
                $query->where('contact_id', $customerId);
            }
            if (Schema::hasColumn($table, 'delivery_id')) {
                $query->where('delivery_id', (int) $delivery->id);
            } elseif (Schema::hasColumn($table, 'vehicle_no') && !empty($delivery->vehicle) && $delivery->vehicle !== '-') {
                $query->where('vehicle_no', (string) $delivery->vehicle);
            }

            $row = $query->orderByDesc(Schema::hasColumn($table, 'last_updated_at') ? 'last_updated_at' : 'updated_at')->first();
            if (!empty($row)) {
                return (object) [
                    'latitude' => $row->latitude ?? $row->lat ?? null,
                    'longitude' => $row->longitude ?? $row->lng ?? null,
                    'status' => $row->status ?? $delivery->status ?? null,
                    'last_updated_at' => $row->last_updated_at ?? $row->updated_at ?? $row->created_at ?? null,
                    'remarks' => $row->remarks ?? null,
                ];
            }
        }

        return null;
    }

    protected function deliveryRows(int $businessId, int $customerId, array $filters = [])
    {
        $rows = collect();

        if (Schema::hasTable('distribution_deliveries')) {
            $query = DB::table('distribution_deliveries')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId);

            $this->applyCommonFilters($query, $filters, 'distribution_deliveries');

            return $query->orderByDesc('id')
                ->limit(250)
                ->get()
                ->map(function ($row) {
                    return $this->normalizeDeliveryRow($row, 'distribution_deliveries');
                });
        }

        if (Schema::hasTable('delivery_notes')) {
            $query = DB::table('delivery_notes')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId);

            $this->applyCommonFilters($query, $filters, 'delivery_notes');

            return $query->orderByDesc('id')
                ->limit(250)
                ->get()
                ->map(function ($row) {
                    return $this->normalizeDeliveryRow($row, 'delivery_notes');
                });
        }

        // Safe fallback: show order/sales records as delivery-tracking candidates.
        if (Schema::hasTable('transactions')) {
            $query = DB::table('transactions')
                ->where('business_id', $businessId)
                ->where('contact_id', $customerId)
                ->whereNull('deleted_at')
                ->whereIn('type', ['sell', 'sales_order', 'route_operation', 'distribution_sales_order']);

            if (!empty($filters['date_from'])) {
                $query->whereDate('transaction_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('transaction_date', '<=', $filters['date_to']);
            }
            if (!empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', $search)
                      ->orWhere('ref_no', 'like', $search)
                      ->orWhere('status', 'like', $search);
                });
            }

            $rows = $query->orderByDesc('transaction_date')
                ->limit(250)
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'id' => $row->id,
                        'delivery_no' => $row->invoice_no ?? $row->ref_no ?? ('DEL-' . $row->id),
                        'order_no' => $row->invoice_no ?? $row->ref_no ?? '-',
                        'vehicle' => $row->vehicle_no ?? '-',
                        'driver' => $row->driver_name ?? '-',
                        'dispatch_date' => $row->transaction_date ?? $row->created_at ?? null,
                        'expected_date' => $row->delivery_date ?? null,
                        'delivered_date' => null,
                        'status' => $this->normalizeStatus($row->status ?? 'pending'),
                        'amount' => (float) ($row->final_total ?? 0),
                        'source_table' => 'transactions',
                    ];
                });
        }

        if (!empty($filters['status'])) {
            $rows = $rows->filter(function ($row) use ($filters) {
                return strtolower((string) $row->status) === strtolower((string) $filters['status']);
            })->values();
        }

        return $rows;
    }

    protected function findDelivery(int $businessId, int $customerId, $id)
    {
        return $this->deliveryRows($businessId, $customerId)->firstWhere('id', (int) $id);
    }

    protected function deliveryItems(int $businessId, int $customerId, $delivery)
    {
        if (!empty($delivery->source_table) && $delivery->source_table === 'transactions' && Schema::hasTable('transaction_sell_lines')) {
            return DB::table('transaction_sell_lines as tsl')
                ->leftJoin('products as p', 'p.id', '=', 'tsl.product_id')
                ->where('tsl.transaction_id', $delivery->id)
                ->select([
                    'p.name as product_name',
                    'tsl.quantity',
                    'tsl.unit_price_inc_tax as unit_price',
                    DB::raw('(COALESCE(tsl.quantity, 0) * COALESCE(tsl.unit_price_inc_tax, 0)) as line_total'),
                ])
                ->get();
        }

        if (Schema::hasTable('distribution_delivery_lines')) {
            return DB::table('distribution_delivery_lines as ddl')
                ->leftJoin('products as p', 'p.id', '=', 'ddl.product_id')
                ->where('ddl.delivery_id', $delivery->id)
                ->select([
                    'p.name as product_name',
                    'ddl.quantity',
                    'ddl.unit_price',
                    DB::raw('(COALESCE(ddl.quantity, 0) * COALESCE(ddl.unit_price, 0)) as line_total'),
                ])
                ->get();
        }

        return collect();
    }

    protected function applyCommonFilters($query, array $filters, string $table): void
    {
        if (!empty($filters['status']) && Schema::hasColumn($table, 'status')) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from']) && Schema::hasColumn($table, 'dispatch_date')) {
            $query->whereDate('dispatch_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to']) && Schema::hasColumn($table, 'dispatch_date')) {
            $query->whereDate('dispatch_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search, $table) {
                foreach (['delivery_no', 'order_no', 'vehicle_no', 'driver_name', 'status'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $q->orWhere($column, 'like', $search);
                    }
                }
            });
        }
    }

    protected function normalizeDeliveryRow($row, string $sourceTable)
    {
        return (object) [
            'id' => $row->id,
            'delivery_no' => $row->delivery_no ?? $row->delivery_number ?? ('DEL-' . $row->id),
            'order_no' => $row->order_no ?? $row->sales_order_no ?? $row->invoice_no ?? '-',
            'vehicle' => $row->vehicle_no ?? $row->vehicle ?? '-',
            'driver' => $row->driver_name ?? $row->driver ?? '-',
            'dispatch_date' => $row->dispatch_date ?? $row->created_at ?? null,
            'expected_date' => $row->expected_delivery_date ?? $row->expected_date ?? null,
            'delivered_date' => $row->delivered_date ?? $row->delivery_date ?? null,
            'status' => $this->normalizeStatus($row->status ?? 'pending'),
            'amount' => (float) ($row->amount ?? $row->final_total ?? $row->total ?? 0),
            'source_table' => $sourceTable,
        ];
    }

    protected function normalizeStatus($status): string
    {
        $status = strtolower(trim((string) $status));

        if (in_array($status, ['final', 'approved'], true)) {
            return 'approved';
        }
        if (in_array($status, ['done', 'completed'], true)) {
            return 'delivered';
        }
        if (empty($status)) {
            return 'pending';
        }

        return $status;
    }

    protected function summaryCards($deliveries): array
    {
        $nowMonth = date('Y-m');

        return [
            'pending' => $deliveries->where('status', 'pending')->count(),
            'in_transit' => $deliveries->whereIn('status', ['dispatched', 'in_transit', 'loaded'])->count(),
            'delivered_this_month' => $deliveries->filter(function ($row) use ($nowMonth) {
                return $row->status === 'delivered' && !empty($row->delivered_date) && date('Y-m', strtotime($row->delivered_date)) === $nowMonth;
            })->count(),
            'total' => $deliveries->count(),
        ];
    }
}
