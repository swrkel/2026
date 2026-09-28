<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerDocumentCategoryController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $enabled = Schema::hasTable('customer_document_categories');
        $categories = $enabled
            ? DB::table('customer_document_categories')->where('business_id', $businessId)->orderBy('name')->paginate(25)
            : collect([]);

        return view('customers::documents.categories')->with(compact('categories', 'enabled'));
    }

    public function store(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        abort_if(!Schema::hasTable('customer_document_categories'), 404);

        $request->validate(['name' => 'required|string|max:255', 'description' => 'nullable|string|max:1000']);

        DB::table('customer_document_categories')->insert([
            'business_id' => $businessId,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_active' => 1,
            'created_by' => $request->session()->get('user.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('customers.document_categories.index')->with('status', ['success' => 1, 'msg' => __('customers::lang.document_category_saved')]);
    }

    public function destroy($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        abort_if(!Schema::hasTable('customer_document_categories'), 404);

        DB::table('customer_document_categories')->where('business_id', $businessId)->where('id', $id)->delete();

        return redirect()->route('customers.document_categories.index')->with('status', ['success' => 1, 'msg' => __('customers::lang.document_category_deleted')]);
    }
}
