<?php
namespace Modules\Product\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Services\ProductSettingsService;
use Modules\Product\Utils\ProductPermissionUtil;
class ProductSettingController extends Controller
{
    public function __construct(private ProductSettingsService $service, private ProductPermissionUtil $permission) {}
    public function index(){ $this->permission->abortUnless('product.settings'); $settings = $this->service->get(); return view('product::settings.index', compact('settings')); }
    public function store(Request $request){ $this->permission->abortUnless('product.settings'); $this->service->save($request->except('_token')); return back()->with('status',['success'=>1,'msg'=>__('product::lang.updated_successfully')]); }
}
