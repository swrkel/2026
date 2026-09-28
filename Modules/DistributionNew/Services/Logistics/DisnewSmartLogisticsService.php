<?php

namespace Modules\DistributionNew\Services\Logistics;

use Illuminate\Support\Arr;
use Modules\DistributionNew\Models\DisnewTripCommission;
use Modules\DistributionNew\Models\DisnewTripExpense;
use Modules\DistributionNew\Models\DisnewVehicleFuelEntry;
use Modules\DistributionNew\Models\DisnewVehicleMaintenance;
use Modules\DistributionNew\Models\DisnewVehicleOdometerHistory;

class DisnewSmartLogisticsService
{
    public function saveFuelEntry(array $data): DisnewVehicleFuelEntry
    {
        $data['total_amount'] = round(((float)($data['litres'] ?? 0)) * ((float)($data['unit_price'] ?? 0)), 4);
        return DisnewVehicleFuelEntry::create($data);
    }

    public function saveOdometer(array $data): DisnewVehicleOdometerHistory
    {
        $opening = (float)($data['opening_odometer'] ?? 0);
        $closing = isset($data['closing_odometer']) ? (float)$data['closing_odometer'] : null;
        $data['distance'] = $closing !== null && $closing >= $opening ? round($closing - $opening, 3) : 0;
        return DisnewVehicleOdometerHistory::create($data);
    }

    public function saveMaintenance(array $data): DisnewVehicleMaintenance
    {
        return DisnewVehicleMaintenance::create($data);
    }

    public function saveTripExpense(array $data): DisnewTripExpense
    {
        return DisnewTripExpense::create($data);
    }

    public function calculateCommission(array $data): DisnewTripCommission
    {
        $base = (float)($data['base_amount'] ?? 0);
        $type = $data['commission_type'] ?? 'fixed';
        $value = (float)($data['commission_value'] ?? 0);
        $data['commission_amount'] = $type === 'percentage' ? round($base * $value / 100, 4) : round($value, 4);
        return DisnewTripCommission::create($data);
    }

    public function vehicleCostSummary(int $businessId, array $filters = []): array
    {
        $from = Arr::get($filters, 'date_from'); $to = Arr::get($filters, 'date_to');
        $fuel = DisnewVehicleFuelEntry::forBusiness($businessId)->when($from, fn($q)=>$q->whereDate('fuel_date','>=',$from))->when($to, fn($q)=>$q->whereDate('fuel_date','<=',$to))->sum('total_amount');
        $maintenance = DisnewVehicleMaintenance::forBusiness($businessId)->when($from, fn($q)=>$q->whereDate('maintenance_date','>=',$from))->when($to, fn($q)=>$q->whereDate('maintenance_date','<=',$to))->sum('cost_amount');
        $expenses = DisnewTripExpense::forBusiness($businessId)->when($from, fn($q)=>$q->whereDate('expense_date','>=',$from))->when($to, fn($q)=>$q->whereDate('expense_date','<=',$to))->sum('amount');
        return ['fuel'=>$fuel, 'maintenance'=>$maintenance, 'trip_expenses'=>$expenses, 'total'=>$fuel+$maintenance+$expenses];
    }
}
