<?php
namespace Modules\Product\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Utils\ProductPermissionUtil;
class ProductImportController extends Controller
{
    public function __construct(private ProductPermissionUtil $permission) {}
    public function index(){ $this->permission->abortUnless('product.import'); return view('product::imports.index'); }
    public function products(Request $request){ $this->permission->abortUnless('product.import'); return back()->with('status',['success'=>1,'msg'=>__('product::lang.import_received')]); }
}
