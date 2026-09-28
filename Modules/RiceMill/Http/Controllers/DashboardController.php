<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Models\ProductionBatch;

class DashboardController extends BaseController
{
    public function index(Request $request)
    {
        $b=$this->bid();
        $state=$this->listTools()->dateState($request,$b);
        $fromDate=$state['range']==='all'?'1900-01-01':$state['from'];
        $toDate=$state['range']==='all'?'2099-12-31':$state['to'];
        $fromTs=Carbon::parse($fromDate)->startOfDay()->toDateTimeString();
        $toTs=Carbon::parse($toDate)->endOfDay()->toDateTimeString();

        // Current stock cards remain live snapshots; activity cards follow the
        // system-standard selected date range.
        $summary=DB::selectOne(
            'SELECT '
            .'(SELECT COALESCE(SUM(balance_qty),0) FROM rcm_paddy_lots WHERE business_id = ?) AS paddy_stock, '
            .'(SELECT COALESCE(SUM(current_qty),0) FROM rcm_products WHERE business_id = ?) AS rice_stock, '
            .'(SELECT COALESCE(SUM(rice_output_qty),0) FROM rcm_production_batches WHERE business_id = ? AND completed_at >= ? AND completed_at <= ?) AS period_production, '
            .'(SELECT COALESCE(SUM(net_total),0) FROM rcm_dispatches WHERE business_id = ? AND dispatch_date >= ? AND dispatch_date <= ? AND status = ?) AS period_sales, '
            .'(SELECT COALESCE(AVG(rice_yield_percent),0) FROM rcm_production_batches WHERE business_id = ? AND status = ? AND completed_at >= ? AND completed_at <= ?) AS avg_yield',
            [$b,$b,$b,$fromTs,$toTs,$b,$fromDate,$toDate,'approved',$b,'completed',$fromTs,$toTs]
        );

        $recentQuery=ProductionBatch::forBusiness($b)
            ->select(['id','business_id','batch_no','status','started_at','completed_at','input_qty','rice_output_qty','rice_yield_percent','cost_per_kg']);
        $this->applyListFilters($recentQuery,$request,['batch_no','status','input_qty','rice_output_qty','rice_yield_percent','cost_per_kg'],'started_at',true);
        $recent=$recentQuery->latest('id')->paginate($this->listPerPage($request,25))->appends($request->query());

        $data=[
            'paddy_stock'=>(float)($summary->paddy_stock??0),
            'rice_stock'=>(float)($summary->rice_stock??0),
            'period_production'=>(float)($summary->period_production??0),
            'period_sales'=>(float)($summary->period_sales??0),
            'avg_yield'=>(float)($summary->avg_yield??0),
            'recent_batches'=>$recent,
            'date_range'=>$state,
        ];

        return view('RiceMill::dashboard',compact('data'));
    }
}
