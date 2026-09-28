<?php
namespace Modules\DistributionNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewTrip;
use Modules\DistributionNew\Services\DisnewTripService;
class DisnewTripController extends Controller { public function index(){ $trips=DisnewTrip::latest()->paginate(25); return view('distributionnew::trips.index', compact('trips')); } public function store(Request $request, DisnewTripService $service){ if(!$service->validateCapacity($request->all())) return back()->withErrors(['capacity'=>'Loaded weight/volume exceeds vehicle capacity.']); $trip=$service->createTrip($request->except('lines'), $request->input('lines',[])); return redirect()->route('distributionnew.trips.index')->with('status','Trip created.'); } }
