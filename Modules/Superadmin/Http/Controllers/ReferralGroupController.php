<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Superadmin\Entities\ReferralGroup;
use Yajra\DataTables\Facades\DataTables;

class ReferralGroupController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $businessUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (request()->ajax()) {
            $referral_agent = ReferralGroup::where('group_name', 'Agent')->first();
            if(empty($referral_agent)){
                ReferralGroup::create([
                    'group_name' => 'Agent',
                    'created_by' => Auth::user()->id,
                    'date' => \Carbon::now()
                ]);
            }
            $referrals = ReferralGroup::leftjoin('users', 'referral_groups.created_by', 'users.id')->select('referral_groups.*', 'users.username as added_by');

            return DataTables::of($referrals)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (auth()->user()->can('superadmin')) {
                        $html .= '<li><a data-href="' . action('\Modules\Superadmin\Http\Controllers\ReferralGroupController@edit', [$row->id]) . '" data-container=".view_modal" class="btn-modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        if (strtolower($row->group_name) !== 'agent') {
                            $html .= '<li><a data-href="' . action('\Modules\Superadmin\Http\Controllers\ReferralGroupController@destroy', [$row->id]) . '" class="delete_referral_group_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        }
                    }

                    $html .= '</ul></div>';
                    return $html;
                })

                ->editColumn('date', '{{@format_date($date)}}')

                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('superadmin::referral.partials.create_referral_group');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $inputs = $request->except('_token');
            $inputs['date'] = $this->moduleUtil->uf_date($inputs['date']);
            $inputs['created_by'] = Auth::user()->id;

            ReferralGroup::create($inputs);
            $output = [
                'success' => true,
                'tab' => 'referral_group',
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'referral_group',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('superadmin::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $group = ReferralGroup::findOrFail($id);
        return view('superadmin::referral.partials.edit_referral_group')->with(compact('group'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $inputs = $request->except('_token', '_method');
            $inputs['date'] = $this->moduleUtil->uf_date($inputs['date']);

            ReferralGroup::where('id', $id)->update($inputs);
            $output = [
                'success' => true,
                'tab' => 'referral_group',
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'referral_group',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $group = ReferralGroup::findOrFail($id);
            if (strtolower($group->group_name) === 'agent') {
                return [
                    'success' => false,
                    'msg' => __('superadmin::lang.agent_group_cannot_be_deleted')
                ];
            }

            $group->delete();
            $output = [
                'success' => true,
                'tab' => 'referral_group',
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'referral_group',
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return $output;
    }
}
