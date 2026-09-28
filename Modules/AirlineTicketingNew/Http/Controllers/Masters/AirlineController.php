<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Masters;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Airline;
use Modules\AirlineTicketingNew\Http\Requests\Masters\SaveAirlineRequest;
use Modules\AirlineTicketingNew\Services\Masters\MasterCrudService;

class AirlineController extends Controller
{
    public function __construct(private readonly MasterCrudService $service)
    {
    }

    public function index(Request $request)
    {
        $records = $this->service->list(Airline::class, (int) session('business.id'), $request->string('search')->toString());
        return view('airlineticketingnew::masters.airlines.index', compact('records'));
    }

    public function create()
    {
        return view('airlineticketingnew::masters.airlines.form', ['record' => new Airline()]);
    }

    public function store(SaveAirlineRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = (int) session('business.id');
        $data['business_location_id'] = $request->integer('business_location_id') ?: null;
        $data['store_id'] = $request->integer('store_id') ?: null;
        $this->service->create(Airline::class, $data);

        return redirect()->route('airline-ticketing-new.masters.airlines.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::messages.saved_successfully')]);
    }

    public function edit(Airline $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::masters.airlines.form', compact('record'));
    }

    public function update(SaveAirlineRequest $request, Airline $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        $this->service->update($record, $request->validated());

        return redirect()->route('airline-ticketing-new.masters.airlines.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::messages.saved_successfully')]);
    }

    public function destroy(Airline $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        $this->service->delete($record);
        return response()->json(['success' => true]);
    }
}
