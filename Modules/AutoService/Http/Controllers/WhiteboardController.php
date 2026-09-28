<?php
namespace Modules\AutoService\Http\Controllers;

use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServicePartMovement;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceBayAllocation;

class WhiteboardController extends AutoServiceBaseController
{
    public function index()
    {
        $statuses = ['received','inspection','estimated','waiting_customer_approval','approved','waiting_parts','in_progress','quality_check','ready','delivered'];
        $jobsByStatus = [];
        foreach ($statuses as $status) {
            $jobsByStatus[$status] = AutoServiceJob::where('status', $status)->orderBy('expected_delivery_date')->orderByDesc('id')->limit(30)->get();
        }
        $todayRevenue = AutoServiceInvoice::whereDate('invoice_date', date('Y-m-d'))->sum('total_amount');
        $partsWaiting = AutoServicePartMovement::whereIn('movement_type', ['reserve','request','back_order'])->where('status','!=','completed')->count();
        $activeBays = AutoServiceBayAllocation::whereNull('released_at')->count();
        return view('autoservice::whiteboard.index', compact('jobsByStatus','todayRevenue','partsWaiting','activeBays'));
    }
}
