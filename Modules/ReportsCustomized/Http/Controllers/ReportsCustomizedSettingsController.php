<?php

namespace Modules\ReportsCustomized\Http\Controllers;

use App\Business;
use App\Category;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Product;
use App\SalesAgent;
use App\Transaction;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Distribution\Entities\DistributionInvoice;
use Modules\Distribution\Entities\DistributionInvoiceCheque;
use Modules\Distribution\Entities\DistributionInvoiceLine;
use Modules\Distribution\Entities\Distribution_discount_product;
use Illuminate\Contracts\Support\Renderable;
use Modules\ReportsCustomized\Entities\LiocReportCustomized;
use Yajra\DataTables\Facades\DataTables;
 

class ReportsCustomizedSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }
    // Modified by Engr. Alex -- task 7882: Issue 3 - add description_constant_details and added_user columns; save created_by on store
    public function index()
{
    $business_id = request()->session()->get('user.business_id');

    if (request()->ajax()) {
        $liocReportCustomized = LiocReportCustomized::leftJoin('users', 'lioc_report_customizeds.created_by', '=', 'users.id')
            ->where('lioc_report_customizeds.business_id', $business_id)
            ->select(
                'lioc_report_customizeds.*',
                \Illuminate\Support\Facades\DB::raw("CONCAT(COALESCE(users.first_name,''),' ',COALESCE(users.last_name,'')) as added_user")
            );

        return DataTables::of($liocReportCustomized)
            ->addColumn('action', function ($row) {
                $editUrl = action([\Modules\ReportsCustomized\Http\Controllers\ReportsCustomizedSettingsController::class, 'edit'], [$row->id]);
                $deleteUrl = action([\Modules\ReportsCustomized\Http\Controllers\ReportsCustomizedSettingsController::class, 'destroy'], [$row->id]);
                return '
                    <button data-href="'.$editUrl.'" data-container=".report_model" class="btn btn-xs btn-primary btn-modal edit_btn">
                        <i class="glyphicon glyphicon-edit"></i> '.__("messages.edit").'
                    </button>
                    <button data-href="'.$deleteUrl.'" class="btn btn-xs btn-danger province_delete">
                        <i class="glyphicon glyphicon-trash"></i> '.__("messages.delete").'
                    </button>
                ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    return view('reportscustomized::settings.index');
}

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $maxStartNumber = LiocReportCustomized::where('business_id', $business_id)
                    ->max('start_number');
        $maxStartNumbers = $maxStartNumber ? $maxStartNumber + 1 : 1;
        
        return view('reportscustomized::settings.create')->with(compact('maxStartNumbers'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
   // Modified by Engr. Alex -- task 7882: Issue 3 - save description_constant_details and created_by
   public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $data = $request->only(['prefix', 'start_number', 'constant_value', 'description_constant_details']);
        $data['business_id'] = $business_id;
        $data['created_by'] = request()->session()->get('user.id');

    $setting = LiocReportCustomized::create($data);
        // For AJAX requests (like from modal), you might return a JSON response
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Setting saved successfully!',
                'data' => $setting
            ]);
        }

        // For normal form submission, redirect back with success message
        return redirect()->back()->with('success', 'Setting saved successfully!');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('reportscustomized::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
public function edit($id)
{
    $report = LiocReportCustomized::findOrFail($id);

    return view('reportscustomized::settings.edit')
        ->with(compact('report'));
}
    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    // Modified by Engr. Alex -- task 7882: Issue 3 - implement update for Prefix & Numbers edit
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $report = LiocReportCustomized::where('business_id', $business_id)->findOrFail($id);
        $report->update($request->only(['prefix', 'start_number', 'constant_value', 'description_constant_details']));

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Updated successfully!']);
        }
        return redirect()->back()->with('success', 'Updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
  public function destroy($id)
{
    $business_id = request()->session()->get('user.business_id');

    // Find the record and ensure it belongs to the current business
    $report = LiocReportCustomized::where('business_id', $business_id)
                ->findOrFail($id);

    // Delete the record
    $report->delete();

    // Return a JSON response for AJAX
    if (request()->ajax()) {
        return response()->json([
            'success' => true,
            'message' => __('Deleted succesfully') // or your own translation key
        ]);
    }

    // Fallback for non-AJAX requests
    return redirect()->back()->with('status', __('Deleted succesfully'));
}
}
