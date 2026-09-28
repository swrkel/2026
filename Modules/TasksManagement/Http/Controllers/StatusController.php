<?php

namespace Modules\TasksManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\TasksManagement\Entities\NoteGroup;
use Modules\TasksManagement\Entities\Task;
use Modules\TasksManagement\Entities\TaskManagementStatus;
use Yajra\DataTables\Facades\DataTables;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $business_id = request()->session()->get('business.id');
        if (request()->ajax()) {
            $taskManagementStatus = TaskManagementStatus::where('business_id', $business_id)
                ->get();

            return DataTables::of($taskManagementStatus)
                ->addColumn(
                    'action',
                    '
                    <button data-href="{{action(\'\Modules\TasksManagement\Http\Controllers\StatusController@edit\',[$id])}}" data-container=".status_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                    <button data-href="{{action(\'\Modules\TasksManagement\Http\Controllers\StatusController@destroy\',[$id])}}" class="btn btn-xs btn-danger status_delete"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                   
                    '
                )
                ->editColumn('default', function ($row) {
                    return ((int) ($row->default ?? 0)) === 1 ? 'Yes' : 'No';
                })
                ->editColumn('color', function ($row) {
                    return '<div style="height: 25px; width: 25px; background: ' . $row->color . '"></div>';
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'color'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        $defaultExit = TaskManagementStatus::where('default', 1)
            ->where('business_id', request()->session()->get('business.id'))
            ->exists();
        return view('tasksmanagement::settings.status.create')->with(compact('defaultExit'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        try {
            $data = $request->except('_token');
            $data['business_id'] = $business_id;

            // Ensure only one default per business
            $isDefault = (int) ($data['default'] ?? 0);
            if ($isDefault === 1) {
                TaskManagementStatus::where('business_id', $business_id)
                    ->update(['default' => 0]);
            }

            $created = TaskManagementStatus::create($data);
            if ($isDefault === 1 && !empty($created->name)) {
                Task::where('business_id', $business_id)->update(['status' => $created->name]);
            }
            session('status.tab', 'status');
            $output = [
                'success' => true,
                'msg' => __('tasksmanagement::lang.status_create_success')
            ];
        } catch (\Exception $e) {
            session('status.tab', 'status');
            dd($e);
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('tasksmanagement::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $status = TaskManagementStatus::findOrFail($id);
        return view('tasksmanagement::settings.status.edit')->with(compact('status'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data['name'] = $request->input('name');
            $data['color'] = $request->input('color');
            $data['default'] = $request->input('default');

            // If this status is set as default, unset default on others for this business
            if ((int) $data['default'] === 1) {
                $business_id = $request->session()->get('business.id');
                TaskManagementStatus::where('business_id', $business_id)
                    ->where('id', '!=', $id)
                    ->update(['default' => 0]);
                // Also update all existing tasks to this default status (by Name)
                $current = TaskManagementStatus::find($id);
                if (!empty($current) && !empty($current->name)) {
                    Task::where('business_id', $business_id)->update(['status' => $current->name]);
                }
            }

            TaskManagementStatus::where('id', $id)->update($data);
            $output = [
                'success' => true,
                'msg' => __('tasksmanagement::lang.status_create_success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        session('status.tab', 'status');
        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        try {
            TaskManagementStatus::where('id', $id)->delete();
            session('status.tab', 'status');
            $output = [
                'success' => true,
                'msg' => __('tasksmanagement::lang.status_delete_success')
            ];
        } catch (\Exception $e) {
            session('status.tab', 'status');
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}
