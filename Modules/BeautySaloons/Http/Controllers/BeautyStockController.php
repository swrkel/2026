<?php
namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyStockMovement;

class BeautyStockController extends Controller
{
    public function index() { $movements = BeautyStockMovement::latest()->paginate(50); return view('beautysaloons::inventory.stock.index', compact('movements')); }
    public function adjust(Request $request) { BeautyStockMovement::create($request->all()); return back()->with('status', 'Stock Updated Successfully'); }
}
