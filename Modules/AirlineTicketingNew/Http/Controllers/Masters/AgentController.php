<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Masters;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\Agent;
use Modules\AirlineTicketingNew\Http\Requests\Masters\SaveAgentRequest;
use Modules\AirlineTicketingNew\Services\Masters\MasterCrudService;

class AgentController extends Controller
{
    public function __construct(private readonly MasterCrudService $service)
    {
    }

    public function index(Request $request)
    {
        $records = $this->service->list(Agent::class, (int) session('business.id'), $request->string('search')->toString());
        return view('airlineticketingnew::masters.agents.index', compact('records'));
    }

    public function create()
    {
        return view('airlineticketingnew::masters.agents.form', ['record' => new Agent()]);
    }

    public function store(SaveAgentRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = (int) session('business.id');
        $data['business_location_id'] = $request->integer('business_location_id') ?: null;
        $data['store_id'] = $request->integer('store_id') ?: null;
        $this->service->create(Agent::class, $data);

        return redirect()->route('airline-ticketing-new.masters.agents.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::messages.saved_successfully')]);
    }

    public function edit(Agent $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        return view('airlineticketingnew::masters.agents.form', compact('record'));
    }

    public function update(SaveAgentRequest $request, Agent $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        $this->service->update($record, $request->validated());

        return redirect()->route('airline-ticketing-new.masters.agents.index')
            ->with('status', ['success' => 1, 'msg' => __('airlineticketingnew::messages.saved_successfully')]);
    }

    public function destroy(Agent $record)
    {
        abort_unless((int) $record->business_id === (int) session('business.id'), 404);
        $this->service->delete($record);
        return response()->json(['success' => true]);
    }
}
