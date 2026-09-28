<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UserGroup;
use App\Business;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UserGroupController extends Controller
{
    protected $businessUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param BusinessUtil $businessUtil
     * @param ModuleUtil $moduleUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;

        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('user_group.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $user_groups = UserGroup::where('business_id', $business_id)
                ->select([
                    'id', 'name', 'description'
                ]);

            return Datatables::of($user_groups)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';
                        
                        if (auth()->user()->can('user_group.update')) {
                            $html .= '<a href="' . action('UserGroupController@edit', $row->id) . '" class="btn btn-xs btn-primary">
                                <i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a>
                            &nbsp;';
                        }
                        
                        if (auth()->user()->can('user_group.delete')) {
                            $html .= '<button data-href="' . action('UserGroupController@destroy', $row->id) . '" class="btn btn-xs btn-danger delete_user_group">
                                <i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';
                        }
                        
                        return $html;
                    }
                )
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('user_groups.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('user_group.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        return view('user_groups.create')->with(compact('business_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('user_group.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $data = $request->only(['name', 'description']);
            $data['business_id'] = $business_id;

            $user_group = UserGroup::create($data);

            $output = [
                'success' => true,
                'msg' => __('user_group.user_group_created_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . "Line: " . $e->getLine() . "Message: " . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('UserGroupController@index')->with('status', $output);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('user_group.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $user_group = UserGroup::where('business_id', $business_id)
            ->findOrFail($id);

        return view('user_groups.edit')->with(compact('user_group', 'business_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('user_group.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $user_group = UserGroup::where('business_id', $business_id)
                ->findOrFail($id);

            $data = $request->only(['name', 'description']);
            $user_group->update($data);

            $output = [
                'success' => true,
                'msg' => __('user_group.user_group_updated_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . "Line: " . $e->getLine() . "Message: " . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->action('UserGroupController@index')->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('user_group.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            
            $user_group = UserGroup::where('business_id', $business_id)
                ->findOrFail($id);
            
            $user_group->delete();
            
            $output = [
                'success' => true,
                'msg' => __('user_group.user_group_deleted_success')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile() . "Line: " . $e->getLine() . "Message: " . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }
}
