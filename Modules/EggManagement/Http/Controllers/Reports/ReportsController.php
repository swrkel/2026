<?php
namespace Modules\EggManagement\Http\Controllers\Reports;

use Illuminate\Routing\Controller;

class ReportsController extends Controller
{
    public function index()
    {
        $reports = [
            ['label' => 'Production Report', 'route' => 'egg.reports.production', 'permission' => 'egg.reports.production', 'icon' => 'fa fa-line-chart', 'description' => 'Daily collection, good eggs and production losses.'],
            ['label' => 'Stock Report', 'route' => 'egg.reports.stock', 'permission' => 'egg.reports.stock', 'icon' => 'fa fa-cubes', 'description' => 'Available stock by grade, lot, location and store.'],
            ['label' => 'Sales Report', 'route' => 'egg.reports.sales', 'permission' => 'egg.reports.sales', 'icon' => 'fa fa-shopping-cart', 'description' => 'Egg sales, quantities, values and customers.'],
            ['label' => 'Purchases Report', 'route' => 'egg.reports.purchases', 'permission' => 'egg.reports.purchases', 'icon' => 'fa fa-truck', 'description' => 'Supplier purchases, quantities, costs and payment status.'],
            ['label' => 'Stock Movements', 'route' => 'egg.reports.movements', 'permission' => 'egg.reports.movements', 'icon' => 'fa fa-exchange', 'description' => 'All egg stock inward and outward movements.'],
            ['label' => 'Wastage Report', 'route' => 'egg.reports.wastage', 'permission' => 'egg.reports.wastage', 'icon' => 'fa fa-trash', 'description' => 'Broken, dirty, rejected and other egg losses.'],
            ['label' => 'Audit Report', 'route' => 'egg.reports.audit', 'permission' => 'egg.reports.audit', 'icon' => 'fa fa-history', 'description' => 'User actions and Egg Management audit history.'],
        ];

        $user = auth()->user();
        if ($user) {
            $reports = array_values(array_filter($reports, function ($report) use ($user) {
                try {
                    return $user->can($report['permission']) || $user->can('egg.*');
                } catch (\Throwable $e) {
                    return true;
                }
            }));
        }

        if (empty($reports)) {
            abort(403, 'You do not have permission to view Egg Management reports.');
        }

        return view('egg::reports.index', compact('reports'));
    }
}
