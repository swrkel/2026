<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\Entities\TailoringWarrantyService;
class TailoringWarrantyServiceController extends Controller
{
    public function index(){ $services = TailoringWarrantyService::latest()->paginate(25); return view('tailoring::warranty.index', compact('services')); }
    public function store(Request $request){ TailoringWarrantyService::create($request->all()); return back()->with('status', 'Warranty/service record saved successfully'); }
}
