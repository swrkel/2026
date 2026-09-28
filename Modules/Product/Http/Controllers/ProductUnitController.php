<?php
namespace Modules\Product\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductUnit;
use Modules\Product\Services\ProductUnitService;
use Modules\Product\Utils\ProductPermissionUtil;
use Yajra\DataTables\Facades\DataTables;
class ProductUnitController extends Controller
{
    public function __construct(private ProductUnitService $service, private ProductPermissionUtil $permission) {}
    public function index(){ $this->permission->abortUnless('product.unit.view'); return view('product::units.index'); }
    public function create(){ $this->permission->abortUnless('product.unit.create'); return view('product::units.create'); }
    public function edit(ProductUnit $unit){ $this->permission->abortUnless('product.unit.edit'); return view('product::units.edit', compact('unit')); }
    public function store(Request $request){ $this->permission->abortUnless('product.unit.create'); $this->service->create($request->except('_token')); return redirect()->route('product.units.index')->with('status',['success'=>1,'msg'=>__('product::lang.saved_successfully')]); }
    public function update(Request $request, ProductUnit $unit){ $this->permission->abortUnless('product.unit.edit'); $this->service->update($unit,$request->except('_token','_method')); return redirect()->route('product.units.index')->with('status',['success'=>1,'msg'=>__('product::lang.updated_successfully')]); }
    public function destroy(ProductUnit $unit){ $this->permission->abortUnless('product.unit.delete'); $this->service->delete($unit); return response()->json(['success'=>true,'msg'=>__('product::lang.deleted_successfully')]); }
    public function datatable(){ $this->permission->abortUnless('product.unit.view'); return DataTables::of($this->service->query())->addColumn('action', fn($row)=>view('product::partials.simple_actions',['row'=>$row,'route'=>'units'])->render())->rawColumns(['action'])->make(true); }
}
