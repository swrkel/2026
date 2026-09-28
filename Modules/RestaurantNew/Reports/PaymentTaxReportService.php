<?php

namespace Modules\RestaurantNew\Reports;

use Illuminate\Support\Facades\DB;

class PaymentTaxReportService
{
    public function payments(array $filters)
    {
        $businessId = $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        return DB::table('restaurant_new_order_payments as p')
            ->join('restaurant_new_orders as o', 'o.id', '=', 'p.order_id')
            ->where('o.business_id', $businessId)
            ->when($filters['location_id'] ?? null, fn($q,$v) => $q->where('o.location_id', $v))
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->where('p.paid_on', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->where('p.paid_on', '<=', $v))
            ->selectRaw('p.payment_method as method, COUNT(*) as payments, SUM(p.amount) as total')
            ->groupBy('p.payment_method')
            ->orderByDesc('total')
            ->get();
    }

    public function taxAndServiceCharge(array $filters)
    {
        $businessId = $filters['business_id'] ?? request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        return DB::table('restaurant_new_orders')
            ->where('business_id', $businessId)
            ->when($filters['location_id'] ?? null, fn($q,$v) => $q->where('location_id', $v))
            ->when($filters['date_from'] ?? null, fn($q,$v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q,$v) => $q->where('created_at', '<=', $v))
            ->selectRaw('DATE(created_at) as report_date, SUM(tax_amount) as tax, SUM(service_charge_amount) as service_charge, SUM(grand_total) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc('report_date')
            ->get();
    }
}
