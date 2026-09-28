<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\NewVehicle;
use DB;
use App\Contact;

class VehicleController extends Controller
{
    /**
     * All Utils instance.
     *
     */


    /**
     * Constructor
     *

     * @return void
     */

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->pluck('name', 'id');
        return view('brand.Vehicle', ['data' => $customers]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $data = new NewVehicle();
        $data->business_id = $business_id;
        $data->customer_id = $request->VehicleclientName;
        $data->vehicle_no = $request->vehicleName;
        $data->save();

        request()->session()->put('vehicle_id', $data->id);
        return back();
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $business_id = $request->session()->get('user.business_id');

        if ($query == '') {
            $vehicles = NewVehicle::where('business_id', $business_id)
                ->limit(50)
                ->get(['id', 'vehicle_no']);
        } else {
            $vehicles = NewVehicle::where('vehicle_no', 'LIKE', "%{$query}%")
                ->where('business_id', $business_id)
                ->limit(50)
                ->get(['id', 'vehicle_no']);
        }

        return response()->json($vehicles);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

    }
}