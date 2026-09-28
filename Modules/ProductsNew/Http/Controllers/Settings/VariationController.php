<?php
namespace Modules\ProductsNew\Http\Controllers\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Settings\ProductSettingLookupService;
use Modules\ProductsNew\Services\Settings\ProductSettingWriteService;

class VariationController extends Controller
{
    public function index(Request $request, ProductSettingLookupService $lookup)
    {
        return view('productsnew::settings.variations.index', ['variations' => $lookup->variations($request->all())]);
    }

    public function store(Request $request, ProductSettingWriteService $writer)
    {
        $data = $request->validate(['name' => 'required|string|max:191']);
        $writer->createVariation($data);
        return redirect()->route('products-new.settings.variations.index')->with('status', __('productsnew::settings.variation_saved'));
    }
}
