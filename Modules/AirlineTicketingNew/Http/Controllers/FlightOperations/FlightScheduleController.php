<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\FlightOperations;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\FlightSchedule;

class FlightScheduleController extends Controller
{
    public function index()
    {
        $records = FlightSchedule::query()
            ->where('business_id', (int) session('business.id'))
            ->orderBy('departure_at')
            ->paginate(50);

        return view('airlineticketingnew::flight-operations.schedules.index', compact('records'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'airline_id' => ['required','integer'],
            'flight_number' => ['required','string','max:20'],
            'origin_airport_id' => ['required','integer'],
            'destination_airport_id' => ['required','integer','different:origin_airport_id'],
            'departure_at' => ['required','date'],
            'arrival_at' => ['required','date','after:departure_at'],
            'aircraft_type_id' => ['nullable','integer'],
            'status' => ['required','in:scheduled,delayed,cancelled,diverted,completed'],
            'is_active' => ['nullable','boolean'],
        ]);

        FlightSchedule::query()->create(array_merge($data, [
            'business_id' => (int) session('business.id'),
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
            'is_active' => $request->boolean('is_active'),
        ]));

        return back()->with('status', ['success' => 1, 'msg' => 'Flight schedule saved successfully.']);
    }
}
