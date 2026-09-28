<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Superadmin\Entities\DefaultBusinessType;
use Yajra\DataTables\Facades\DataTables;


class DefaultBusinessTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (request()->ajax()) {
            $business_types = DefaultBusinessType::where('id', '>=', 1)
                ->select('id', 'business_type', 'added_by', 'created_at as date');

            return DataTables::of($business_types)
                ->addColumn('added_by_name', function ($item) {
                    return $item->addedBy->username ?? 'N/A';
                })
                ->addColumn(
                    'action',
                    '
                <button data-href="{{action(\'\Modules\Superadmin\Http\Controllers\DefaultBusinessTypeController@edit\',[$id])}}" data-container=".business_type_modal" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                <button data-href="{{action(\'\Modules\Superadmin\Http\Controllers\DefaultBusinessTypeController@destroy\',[$id])}}" class="btn btn-xs btn-danger business_type_delete"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                '
                )
                ->editColumn('date', function ($item) {
                    $date = $item->date;
                    $date = date_create($date);
                    return $date->format('Y-m-d H:i');
                })
                ->rawColumns(['action', 'date'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('superadmin::superadmin_settings.default_business_types.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->except(['_token', '_method']);
            $rules = [
                'business_type' => 'required|unique:default_business_types,business_type',
            ];
            $validator = \Validator::make($data, $rules);
            if ($validator->fails()) {
                $output = [
                    'success' => false,
                    'msg' => $validator->errors()->first(),
                ];
                return response()->json($output);
            }

            try {
                $data['added_by'] = Auth::user()->id;
                DefaultBusinessType::create($data);

                $output = [
                    'success' => true,
                    'msg' => __('superadmin::lang.business_type_created_successfully')
                ];
            } catch (\Exception $e) {
                $output = [
                    'success' => false,
                    'msg' => __('lang_v1.something_went_wrong'),
                ];
            }

            return response()->json($output);
        }

        // Fallback for non-ajax requests
        $data = $request->except(['_token', '_method']);
        $rules = [
            'business_type' => 'required|unique:default_business_types,business_type',
        ];
        $validator = \Validator::make($data, $rules);
        if ($validator->fails()) {
            $output = [
                'success' => false,
                'msg' => __('lang_v1.fill_required_fields'),
            ];
            return redirect()->back()->with('status', $output);
        }

        $data['added_by'] = Auth::user()->id;
        $business_type = DefaultBusinessType::create($data);

        if (!$business_type) {
            $output = [
                'success' => false,
                'msg' => __('lang_v1.something_went_wrong'),
            ];
            return redirect()->back()->with('status', $output);
        }

        $output = [
            'success' => true,
            'tab' => 'default_business_types',
            'msg' => __('superadmin::lang.business_type_created_successfully')
        ];
        return redirect()->back()->with('status', $output);
    }


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_type = DefaultBusinessType::findOrFail($id);
        return view('superadmin::superadmin_settings.default_business_types.edit')->with(compact('business_type'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $data = $request->except(['_token', '_method']);
        $rules = [
            'business_type' => 'required|unique:default_business_types,business_type,' . $id,
        ];
        $validator = \Validator::make($data, $rules);
        if ($validator->fails()) {
            $output = [
                'success' => false,
                'msg' => __('lang_v1.fill_required_fields'),
            ];
            return redirect()->back()->with('status', $output);
        }
        $business_type = DefaultBusinessType::where('id', $id)->update($data);
        if (!$business_type) {
            $output = [
                'success' => false,
                'msg' => __('lang_v1.something_went_wrong'),
            ];
            return redirect()->back()->with('status', $output);
        }
        $output = [
            'success' => true,
            'tab' => 'default_business_types',
            'msg' => __('superadmin::lang.business_type_updated_successfully')
        ];
        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $business_type = DefaultBusinessType::where('id', $id)->delete();

        if (!$business_type) {
            $output = [
                'success' => false,
                'msg' => __('lang_v1.something_went_wrong'),
            ];
            return redirect()->back()->with('status', $output);
        }

        $output = [
            'success' => true,
            'tab' => 'default_business_types',
            'msg' => __('superadmin::lang.business_type_deleted_successfully')
        ];
        return redirect()->back()->with('status', $output);
    }

    public function allBusinessTypes()
    {
        $businessTypes = DefaultBusinessType::get()->pluck('business_type', 'id');
        return response()->json($businessTypes);
    }
}