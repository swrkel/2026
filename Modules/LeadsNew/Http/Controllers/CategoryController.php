<?php

namespace Modules\LeadsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\LeadsNew\Entities\LeadsCategory;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    /**
     * Check whether the tenant database has parent category support.
     *
     * Some tenant databases have only these columns in leads_new_categories:
     * id, business_id, date, name, created_by, created_at, updated_at.
     * The old code always joined leads_new_categories.parent_id, causing:
     * SQLSTATE[42S22]: Unknown column leads_new_categories.parent_id.
     */
    private function hasParentCategoryColumn(): bool
    {
        try {
            return Schema::hasColumn('leads_new_categories', 'parent_id');
        } catch (\Throwable $e) {
            Log::warning('Leads category parent_id column check failed', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('business.id');

        if (request()->ajax()) {
            $has_parent_category_column = $this->hasParentCategoryColumn();

            $leads_new_categories = LeadsCategory::leftJoin('users', 'leads_new_categories.created_by', '=', 'users.id')
                ->where('leads_new_categories.business_id', $business_id);

            if ($has_parent_category_column) {
                $leads_new_categories->leftJoin('leads_new_categories as parent_c', 'leads_new_categories.parent_id', '=', 'parent_c.id')
                    ->select([
                        'leads_new_categories.*',
                        'parent_c.name as parent_cat',
                        'users.username as user',
                    ]);
            } else {
                $leads_new_categories->select([
                    'leads_new_categories.*',
                    'users.username as user',
                ]);
            }

            if (!empty(request()->category)) {
                $leads_new_categories->where('leads_new_categories.id', request()->category);
            }

            if (!empty(request()->user)) {
                $leads_new_categories->where('leads_new_categories.created_by', request()->user);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $leads_new_categories->whereDate('leads_new_categories.date', '>=', request()->start_date);
                $leads_new_categories->whereDate('leads_new_categories.date', '<=', request()->end_date);
            }

            return DataTables::of($leads_new_categories)
                ->addColumn('action', function ($row) {
                    $edit_url = action('\Modules\LeadsNew\Http\Controllers\CategoryController@edit', [$row->id]);
                    $delete_url = action('\Modules\LeadsNew\Http\Controllers\CategoryController@destroy', [$row->id]);

                    return '<button data-href="' . e($edit_url) . '" data-container=".category_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button>
                        <button data-href="' . e($delete_url) . '" class="btn btn-xs btn-danger leads_category_delete"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';
                })
                ->editColumn('name', function ($row) use ($has_parent_category_column) {
                    if (!$has_parent_category_column || empty($row->parent_cat)) {
                        return e($row->name);
                    }

                    return '<b>' . e($row->parent_cat) . '</b><br>&nbsp;&nbsp;&nbsp;&nbsp;---- ' . e($row->name);
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->removeColumn('id')
                ->rawColumns(['action', 'name'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('business.id');
        $supports_parent_category = $this->hasParentCategoryColumn();
        $categories = collect();

        if ($supports_parent_category) {
            $categories = LeadsCategory::where('business_id', $business_id)->pluck('name', 'id');
        }

        return view('leadsnew::settings.category.create')->with(compact('categories', 'supports_parent_category'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('business.id');

        try {
            $input = [];
            $input['date'] = !empty($request->date) ? \Carbon::parse($request->date)->format('Y-m-d') : date('Y-m-d');
            $input['name'] = $request->name;
            $input['created_by'] = Auth::user()->id;
            $input['business_id'] = $business_id;

            if ($this->hasParentCategoryColumn()) {
                $input['parent_id'] = $request->parent_id;
            }

            LeadsCategory::create($input);

            $output = [
                'success' => true,
                'tab' => 'category',
                'msg' => __('leadsnew::lang.category_create_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'category',
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show()
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('business.id');
        $category = LeadsCategory::where('business_id', $business_id)->findOrFail($id);
        $supports_parent_category = $this->hasParentCategoryColumn();
        $categories = collect();

        if ($supports_parent_category) {
            $categories = LeadsCategory::where('business_id', $business_id)
                ->where('id', '!=', $id)
                ->pluck('name', 'id');
        }

        return view('leadsnew::settings.category.edit')->with(compact('category', 'categories', 'supports_parent_category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('business.id');

        try {
            $input = [];
            $input['date'] = !empty($request->date) ? \Carbon::parse($request->date)->format('Y-m-d') : date('Y-m-d');
            $input['name'] = $request->name;

            if ($this->hasParentCategoryColumn()) {
                $input['parent_id'] = $request->parent_id;
            }

            LeadsCategory::where('business_id', $business_id)->where('id', $id)->update($input);

            $output = [
                'success' => true,
                'tab' => 'category',
                'msg' => __('leadsnew::lang.category_update_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'category',
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return array
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('business.id');

        try {
            LeadsCategory::where('business_id', $business_id)->where('id', $id)->delete();

            $output = [
                'success' => true,
                'msg' => __('leadsnew::lang.category_delete_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
