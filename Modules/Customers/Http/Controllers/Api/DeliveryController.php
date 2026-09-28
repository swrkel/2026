<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeliveryController extends BaseDealerApiController
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('customer_portal_deliveries')) {
            return $this->success(['rows' => []]);
        }

        $query = DB::table('customer_portal_deliveries')
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request));

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $rows = $query->orderByDesc('dispatch_date')
            ->orderByDesc('id')
            ->limit($this->paginateLimit($request, 100, 500))
            ->get();

        return $this->success(['rows' => $rows]);
    }

    public function show(Request $request, $id)
    {
        if (!Schema::hasTable('customer_portal_deliveries')) {
            return $this->fail('Delivery table is not available.', 404);
        }

        $row = DB::table('customer_portal_deliveries')
            ->where('business_id', $this->businessId($request))
            ->where('contact_id', $this->customerId($request))
            ->where('id', (int) $id)
            ->first();

        if (empty($row)) {
            return $this->fail('Delivery not found.', 404);
        }

        return $this->success(['delivery' => $row]);
    }

    /**
     * CUS-032: Active delivery tracking list for dealer mobile apps.
     */
    public function tracking(Request $request)
    {
        $rows = $this->deliveryQuery($request)
            ->orderByDesc('dispatch_date')
            ->orderByDesc('id')
            ->limit($this->paginateLimit($request, 100, 500))
            ->get()
            ->map(function ($row) use ($request) {
                $row->timeline = $this->timelineRows($request, $row);
                $row->latest_location = $this->latestLocation($request, $row);
                return $row;
            });

        return $this->success(['rows' => $rows]);
    }

    /**
     * CUS-032: Tracking detail for one delivery.
     */
    public function trackingShow(Request $request, $id)
    {
        $row = $this->deliveryQuery($request)
            ->where('id', (int) $id)
            ->first();

        if (empty($row)) {
            return $this->fail('Delivery not found.', 404);
        }

        return $this->success([
            'delivery' => $row,
            'timeline' => $this->timelineRows($request, $row),
            'latest_location' => $this->latestLocation($request, $row),
        ]);
    }

    /**
     * CUS-032: Latest vehicle GPS location.
     */
    public function vehicleLocation(Request $request)
    {
        $deliveryId = (int) $request->get('delivery_id', 0);
        $vehicleNo = (string) $request->get('vehicle_no', '');
        $row = null;

        if ($deliveryId > 0) {
            $delivery = $this->deliveryQuery($request)->where('id', $deliveryId)->first();
            if (!empty($delivery)) {
                $row = $this->latestLocation($request, $delivery);
            }
        } elseif ($vehicleNo !== '') {
            $row = $this->latestLocationByVehicle($request, $vehicleNo);
        }

        if (empty($row)) {
            return $this->success(['location' => null], 'No GPS location is available yet.');
        }

        return $this->success(['location' => $row]);
    }

    protected function deliveryQuery(Request $request)
    {
        if (Schema::hasTable('customer_portal_deliveries')) {
            $query = DB::table('customer_portal_deliveries')
                ->where('business_id', $this->businessId($request))
                ->where('contact_id', $this->customerId($request));
        } elseif (Schema::hasTable('transactions')) {
            $query = DB::table('transactions')
                ->where('business_id', $this->businessId($request))
                ->where('contact_id', $this->customerId($request))
                ->whereNull('deleted_at')
                ->whereIn('type', ['sell', 'sales_order', 'route_operation', 'distribution_sales_order']);
        } else {
            return DB::table(DB::raw('(select 1 as id) as empty'))->whereRaw('1=0');
        }

        if ($request->filled('status') && Schema::hasColumn($query->from ?? 'customer_portal_deliveries', 'status')) {
            $query->where('status', $request->get('status'));
        }

        return $query;
    }

    protected function timelineRows(Request $request, $delivery)
    {
        foreach (['customer_portal_delivery_tracking', 'distribution_delivery_tracking', 'delivery_tracking'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)->where('business_id', $this->businessId($request));
            if (Schema::hasColumn($table, 'contact_id')) {
                $query->where('contact_id', $this->customerId($request));
            }
            if (Schema::hasColumn($table, 'delivery_id')) {
                $query->where('delivery_id', (int) $delivery->id);
            } elseif (Schema::hasColumn($table, 'delivery_no') && !empty($delivery->delivery_no)) {
                $query->where('delivery_no', $delivery->delivery_no);
            }

            $rows = $query->orderBy('created_at')->get();
            if ($rows->count() > 0) {
                return $rows;
            }
        }

        $status = $delivery->status ?? 'pending';
        return collect([
            ['status' => 'pending', 'label' => 'Pending', 'datetime' => $delivery->created_at ?? null],
            ['status' => $status, 'label' => ucwords(str_replace('_', ' ', $status)), 'datetime' => $delivery->dispatch_date ?? $delivery->transaction_date ?? null],
        ]);
    }

    protected function latestLocation(Request $request, $delivery)
    {
        foreach (['customer_portal_vehicle_locations', 'distribution_vehicle_locations', 'vehicle_locations'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)->where('business_id', $this->businessId($request));
            if (Schema::hasColumn($table, 'contact_id')) {
                $query->where('contact_id', $this->customerId($request));
            }
            if (Schema::hasColumn($table, 'delivery_id')) {
                $query->where('delivery_id', (int) $delivery->id);
            } elseif (!empty($delivery->vehicle_no ?? $delivery->vehicle ?? null) && Schema::hasColumn($table, 'vehicle_no')) {
                $query->where('vehicle_no', $delivery->vehicle_no ?? $delivery->vehicle);
            }

            $orderColumn = Schema::hasColumn($table, 'last_updated_at') ? 'last_updated_at' : (Schema::hasColumn($table, 'updated_at') ? 'updated_at' : 'id');
            $row = $query->orderByDesc($orderColumn)->first();
            if (!empty($row)) {
                return $row;
            }
        }

        return null;
    }

    protected function latestLocationByVehicle(Request $request, string $vehicleNo)
    {
        foreach (['customer_portal_vehicle_locations', 'distribution_vehicle_locations', 'vehicle_locations'] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'vehicle_no')) {
                continue;
            }
            $query = DB::table($table)
                ->where('business_id', $this->businessId($request))
                ->where('vehicle_no', $vehicleNo);
            if (Schema::hasColumn($table, 'contact_id')) {
                $query->where('contact_id', $this->customerId($request));
            }
            $orderColumn = Schema::hasColumn($table, 'last_updated_at') ? 'last_updated_at' : (Schema::hasColumn($table, 'updated_at') ? 'updated_at' : 'id');
            $row = $query->orderByDesc($orderColumn)->first();
            if (!empty($row)) {
                return $row;
            }
        }
        return null;
    }

}
