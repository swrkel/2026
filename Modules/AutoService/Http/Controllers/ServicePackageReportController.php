<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicePackageReportController extends AutoServiceBaseController
{
    public function usage(Request $request)
    {
        $rows=DB::table('auto_service_job_packages as jp')
            ->leftJoin('auto_service_service_packages as p','p.id','=','jp.package_id')
            ->leftJoin('auto_service_jobs as j','j.id','=','jp.job_id')
            ->where('jp.business_id',$this->businessId())
            ->when($request->filled('from'),fn($q)=>$q->whereDate('j.job_date','>=',$request->from))
            ->when($request->filled('to'),fn($q)=>$q->whereDate('j.job_date','<=',$request->to))
            ->groupBy('jp.package_id','jp.package_name')
            ->select('jp.package_id','jp.package_name',DB::raw('COUNT(*) usage_count'),DB::raw('SUM(jp.package_price) revenue'))
            ->orderByDesc('usage_count')->paginate(50)->withQueryString();
        return view('autoservice::package_reports.usage',compact('rows'));
    }

    public function profitability(Request $request)
    {
        $rows=DB::table('auto_service_job_packages as jp')
            ->leftJoin('auto_service_package_lines as pl','pl.package_id','=','jp.package_id')
            ->leftJoin('auto_service_jobs as j','j.id','=','jp.job_id')
            ->where('jp.business_id',$this->businessId())
            ->when($request->filled('from'),fn($q)=>$q->whereDate('j.job_date','>=',$request->from))
            ->when($request->filled('to'),fn($q)=>$q->whereDate('j.job_date','<=',$request->to))
            ->groupBy('jp.package_id','jp.package_name')
            ->select(
                'jp.package_id','jp.package_name',
                DB::raw('COUNT(DISTINCT jp.id) usage_count'),
                DB::raw('SUM(DISTINCT jp.package_price) revenue'),
                DB::raw("SUM(CASE WHEN pl.component_type='stock_item' THEN pl.line_total ELSE 0 END) component_value"),
                DB::raw("SUM(DISTINCT jp.package_price)-SUM(CASE WHEN pl.component_type='stock_item' THEN pl.line_total ELSE 0 END) gross_margin")
            )->orderByDesc('revenue')->paginate(50)->withQueryString();
        return view('autoservice::package_reports.profitability',compact('rows'));
    }

    public function stockConsumption(Request $request)
    {
        $rows=DB::table('auto_service_package_stock_movements as m')
            ->leftJoin('auto_service_service_packages as p','p.id','=','m.package_id')
            ->leftJoin('products as pr','pr.id','=','m.product_id')
            ->where('m.business_id',$this->businessId())
            ->when($request->filled('from'),fn($q)=>$q->whereDate('m.created_at','>=',$request->from))
            ->when($request->filled('to'),fn($q)=>$q->whereDate('m.created_at','<=',$request->to))
            ->groupBy('m.package_id','p.name','m.product_id','pr.name')
            ->select('p.name as package_name','pr.name as product_name',DB::raw('ABS(SUM(m.quantity)) quantity_used'),DB::raw('SUM(ABS(m.quantity)*m.unit_price) sales_value'))
            ->orderByDesc('quantity_used')->paginate(100)->withQueryString();
        return view('autoservice::package_reports.stock_consumption',compact('rows'));
    }
}
