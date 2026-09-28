<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CommunicationHub\Entities\CommunicationHubCampaign;
use Modules\CommunicationHub\Services\Campaigns\CampaignManagerService;

class CommunicationHubCampaignController extends Controller
{
    protected CampaignManagerService $service;

    public function __construct(CampaignManagerService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('communicationhub::campaigns.index', [
            'campaigns' => CommunicationHubCampaign::latest()->paginate(25),
            'summary' => $this->service->summary(),
        ]);
    }

    public function create()
    {
        return view('communicationhub::campaigns.form', ['campaign' => new CommunicationHubCampaign()]);
    }

    public function store(Request $request)
    {
        $campaign = $this->service->save(new CommunicationHubCampaign(), $request->all());
        return redirect()->route('communicationhub.campaigns.index')->with('status', 'Campaign created successfully.');
    }

    public function edit(CommunicationHubCampaign $campaign)
    {
        return view('communicationhub::campaigns.form', compact('campaign'));
    }

    public function update(Request $request, CommunicationHubCampaign $campaign)
    {
        $this->service->save($campaign, $request->all());
        return redirect()->route('communicationhub.campaigns.index')->with('status', 'Campaign updated successfully.');
    }

    public function destroy(CommunicationHubCampaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('communicationhub.campaigns.index')->with('status', 'Campaign deleted successfully.');
    }

    public function schedule(CommunicationHubCampaign $campaign)
    {
        $this->service->schedule($campaign);
        return back()->with('status', 'Campaign scheduled.');
    }

    public function cancel(CommunicationHubCampaign $campaign)
    {
        $this->service->cancel($campaign);
        return back()->with('status', 'Campaign cancelled.');
    }
}
