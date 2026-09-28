<?php
namespace Modules\ProductsNew\Http\Controllers\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Settings\ProductSettingLookupService;
use Modules\ProductsNew\Services\Settings\ProductSettingWriteService;

class BrandController extends Controller
{
    public function index(Request $request, ProductSettingLookupService $lookup)
    {
        return view('productsnew::settings.brands.index', ['brands' => $lookup->brands($request->all())]);
    }

    public function store(Request $request, ProductSettingWriteService $writer)
    {
        $data = $request->validate(['name' => 'required|string|max:191', 'description' => 'nullable|string|max:500']);
        $writer->createBrand($data);
        return redirect()->route('products-new.settings.brands.index')->with('status', __('productsnew::settings.brand_saved'));
    }
}
