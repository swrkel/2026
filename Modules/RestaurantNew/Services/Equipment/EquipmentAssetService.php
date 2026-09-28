<?php

namespace Modules\RestaurantNew\Services\Equipment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewEquipmentAsset;
use Modules\RestaurantNew\Entities\RestaurantNewEquipmentSparePart;
use Modules\RestaurantNew\Entities\RestaurantNewEquipmentWorkOrder;
use Modules\RestaurantNew\Entities\RestaurantNewEquipmentMaintenanceSchedule;
use Modules\RestaurantNew\Entities\RestaurantNewEquipmentAlert;

class EquipmentAssetService
{
    protected function businessId(array $data = []) { return $data['business_id'] ?? session('business.id') ?? request()->session()->get('user.business_id'); }
    protected function locationId(array $data = []) { return $data['location_id'] ?? request()->get('location_id') ?? null; }

    public function dashboard(Request $request): array
    {
        $businessId = $this->businessId();
        return [
            'totalAssets' => RestaurantNewEquipmentAsset::where('business_id', $businessId)->count(),
            'workingAssets' => RestaurantNewEquipmentAsset::where('business_id', $businessId)->where('status', 'working')->count(),
            'maintenanceAssets' => RestaurantNewEquipmentAsset::where('business_id', $businessId)->whereIn('status', ['under_maintenance','out_of_service'])->count(),
            'openWorkOrders' => RestaurantNewEquipmentWorkOrder::where('business_id', $businessId)->whereIn('status', ['open','in_progress'])->count(),
            'dueSchedules' => RestaurantNewEquipmentMaintenanceSchedule::where('business_id', $businessId)->where('is_active', 1)->whereDate('next_due_date', '<=', now()->toDateString())->count(),
            'lowSpareParts' => RestaurantNewEquipmentSparePart::where('business_id', $businessId)->whereColumn('current_stock', '<=', 'reorder_level')->count(),
            'alerts' => RestaurantNewEquipmentAlert::where('business_id', $businessId)->where('status', 'open')->latest()->limit(20)->get(),
        ];
    }

    public function assets(Request $request) { return RestaurantNewEquipmentAsset::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function storeAsset(array $data) { $data['business_id'] = $this->businessId($data); $data['location_id'] = $this->locationId($data); return RestaurantNewEquipmentAsset::create($data); }
    public function workOrders(Request $request) { return RestaurantNewEquipmentWorkOrder::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function spareParts(Request $request) { return RestaurantNewEquipmentSparePart::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function schedules(Request $request) { return RestaurantNewEquipmentMaintenanceSchedule::where('business_id', $this->businessId())->latest()->paginate(25); }
    public function alerts(Request $request) { return RestaurantNewEquipmentAlert::where('business_id', $this->businessId())->latest()->paginate(25); }

    public function storeWorkOrder(array $data)
    {
        $data['business_id'] = $this->businessId($data);
        $data['location_id'] = $this->locationId($data);
        $data['requested_at'] = $data['requested_at'] ?? now();
        $data['work_order_no'] = $data['work_order_no'] ?? 'RNEQ-WO-' . now()->format('YmdHis');
        return RestaurantNewEquipmentWorkOrder::create($data);
    }

    public function closeWorkOrder($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $workOrder = RestaurantNewEquipmentWorkOrder::where('business_id', $this->businessId())->findOrFail($id);
            $workOrder->fill([
                'status' => 'completed',
                'completed_at' => now(),
                'labour_cost' => $data['labour_cost'] ?? $workOrder->labour_cost,
                'parts_cost' => $data['parts_cost'] ?? $workOrder->parts_cost,
                'downtime_hours' => $data['downtime_hours'] ?? $workOrder->downtime_hours,
                'resolution_note' => $data['resolution_note'] ?? $workOrder->resolution_note,
            ])->save();
            RestaurantNewEquipmentAsset::where('id', $workOrder->asset_id)->update(['status' => 'working']);
            return $workOrder;
        });
    }

    public function reports(Request $request): array
    {
        $businessId = $this->businessId();
        return [
            'assetRegister' => RestaurantNewEquipmentAsset::where('business_id', $businessId)->get(),
            'maintenanceHistory' => RestaurantNewEquipmentWorkOrder::where('business_id', $businessId)->latest()->get(),
            'warrantyExpiring' => RestaurantNewEquipmentAsset::where('business_id', $businessId)->whereDate('warranty_expiry', '<=', now()->addDays(30))->get(),
            'lowStockParts' => RestaurantNewEquipmentSparePart::where('business_id', $businessId)->whereColumn('current_stock', '<=', 'reorder_level')->get(),
        ];
    }
}
