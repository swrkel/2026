<?php
namespace Modules\DistributionNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewWarehouse;
use Modules\DistributionNew\Services\DisnewBusinessLimitService;
class DisnewWarehouseController extends Controller { public function index(){ $warehouses=DisnewWarehouse::latest()->paginate(25); return view('distributionnew::warehouses.index', compact('warehouses')); } public function store(Request $request, DisnewBusinessLimitService $limits){ $limits->assertLimit($request->business_id,'max_warehouses','disnew_warehouses','is_active'); DisnewWarehouse::create($request->all()); return back()->with('status','Warehouse saved.'); } }
