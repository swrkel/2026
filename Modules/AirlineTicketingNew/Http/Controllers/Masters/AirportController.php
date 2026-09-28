<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Masters;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Airport;
use Modules\AirlineTicketingNew\Http\Requests\Masters\SaveAirportRequest;
use Modules\AirlineTicketingNew\Services\Masters\MasterCrudService;

class AirportController extends Controller
{
    public function __construct(private readonly MasterCrudService $service)
    {
    }

    public function index(Request $request)
    {
        $records = $this->service->list(Airport::class, (int) session('business.id'), $request->string('search')->toString());
        return view('airlineticketingnew::masters.airports.index', compact('records'));
    }

    public function create()
    {
        return view('airlineticketingnew::masters.airports.form', ['record' => new Airport()]);
    }

    public function store(SaveAirportRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = (int) session('business.id');
        $data['business_location_id'] = $request->integer('business_location_id') ?: null;
        $data['store_id'] = $request->integer('store_id') ?: null;
        $this->service->create(Airport::class, $data);

        return redirect()->route('airline-ticketing-new.masters.airports.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::messages.saved_successfully')]);
    }

    public function edit(Airport $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::masters.airports.form', compact('record'));
    }

    public function update(SaveAirportRequest $request, Airport $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        $this->service->update($record, $request->validated());

        return redirect()->route('airline-ticketing-new.masters.airports.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::messages.saved_successfully')]);
    }

    public function destroy(Airport $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        $this->service->delete($record);
        return response()->json(['success' => true]);
    }
}
