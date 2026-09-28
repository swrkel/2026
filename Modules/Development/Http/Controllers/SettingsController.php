<?php

namespace Modules\Development\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Yajra\DataTables\Facades\DataTables;
use Modules\Development\Entities\DevelopmentModule;

class SettingsController extends \App\Http\Controllers\Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!DevelopmentModule::hasDevelopmentModuleAccess()){
            abort(403, 'Unauthorized Access');
        }
        if (request()->ajax()) {
            $modules = DevelopmentModule::select(['id', 'name', 'created_at']);

            return Datatables::of($modules)
                ->addColumn('action', function ($row) {
                    return '
                        <a href="' . route('development.settings.edit', $row->id) . '" class="btn btn-xs btn-primary">
                            <i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '
                        </a>
                        &nbsp;
                        <button data-href="' . route('development.settings.destroy', $row->id) . '" class="btn btn-xs btn-danger delete-module">
                            <i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '
                        </button>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('development::settings.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('development::settings.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:development_modules|max:255',
        ]);
    
        try {
            DB::beginTransaction();
    
            DevelopmentModule::create([
                'name' => $validated['name']
            ]);
    
            DB::commit();
    
            $output = [
                'success' => 1,
                'msg' => __('development::lang.module_added_successfully'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
    
            \Log::error('Module Store Error: ' . $e->getMessage());
    
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    
        return redirect()
            ->route('development.settings.index')
            ->with('status', $output);
    }    

    /**
     * Show the form for editing the specified resource.
     * @param  \Modules\Development\Entities\DevelopmentModule  $module
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $module = DevelopmentModule::findOrFail($id);

        return view('development::settings.edit', compact('module'));
    } 

    /**
     * Update the specified resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @param  \Modules\Development\Entities\DevelopmentModule  $module
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DevelopmentModule $module)
    {
        $validated = $request->validate([
            'name' => 'required|unique:development_modules,name,' . $module->id . '|max:255',
        ]);

        $module->update($validated);

        $output = [
            'success' => 1,
            'msg' => __('development::lang.module_updated_successfully'),
        ];

        return redirect()
            ->route('development.settings.index')
            ->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param  \Modules\Development\Entities\DevelopmentModule  $module
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $module = DevelopmentModule::findOrFail($id);
            $module->delete();
    
            return response()->json(['success' => true, 'msg' => __('development::lang.module_deleted_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }
}
