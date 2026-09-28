<?php
namespace Modules\ProductsNew\Http\Controllers\Settings;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Settings\ProductSettingLookupService;
use Modules\ProductsNew\Services\Settings\ProductSettingWriteService;

class UnitController extends Controller
{
    public function index(Request $request, ProductSettingLookupService $lookup)
    {
        return view('productsnew::settings.units.index', ['units' => $lookup->units($request->all())]);
    }

    public function store(Request $request, ProductSettingWriteService $writer)
    {
        $data = $request->validate(['actual_name' => 'required|string|max:191', 'short_name' => 'required|string|max:50', 'allow_decimal' => 'nullable|boolean']);
        $writer->createUnit($data);
        return redirect()->route('products-new.settings.units.index')->with('status', __('productsnew::settings.unit_saved'));
    }
}
