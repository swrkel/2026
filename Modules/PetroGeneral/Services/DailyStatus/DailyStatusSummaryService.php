<?php

namespace Modules\PetroGeneral\Services\DailyStatus;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyStatusSummaryService
{
    public function getSummary(int $businessId, Request $request): array
    {
        return [
            'meter_sales' => DB::table('meter_sales')->where('business_id', $businessId)->count(),
            'payments' => DB::table('pump_operator_payments')->where('business_id', $businessId)->count(),
            'other_sales' => DB::table('pump_operator_other_sales')->where('business_id', $businessId)->count(),
        ];
    }

    public function getSales(int $businessId, Request $request)
    {
        return DB::table('meter_sales')->where('business_id', $businessId)->latest('id')->limit(50)->get();
    }

    public function getPayments(int $businessId, Request $request)
    {
        return DB::table('pump_operator_payments')->where('business_id', $businessId)->latest('id')->limit(50)->get();
    }

    public function getStock(int $businessId, Request $request)
    {
        return DB::table('fuel_tanks')->where('business_id', $businessId)->latest('id')->limit(50)->get();
    }
}
