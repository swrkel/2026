<?php
namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyRetailSale;
use Modules\BeautySaloons\Services\BeautyRetailSaleService;

class BeautyRetailSaleController extends Controller
{
    public function index() { $sales = BeautyRetailSale::latest()->paginate(25); return view('beautysaloons::inventory.retail_sales.index', compact('sales')); }
    public function create() { return view('beautysaloons::inventory.retail_sales.create'); }
    public function store(Request $request, BeautyRetailSaleService $service) { $service->createSale($request->except('lines'), $request->input('lines', [])); return redirect()->route('beauty_saloons.inventory.retail_sales.index')->with('status', 'Retail Sale Saved Successfully'); }
}
