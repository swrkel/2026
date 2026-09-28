<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\CommunicationHub\Entities\CommunicationHubMarketplacePackage;
use Modules\CommunicationHub\Services\Marketplace\MarketplacePackageService;

class CommunicationHubMarketplaceController extends Controller
{
    protected MarketplacePackageService $marketplace;

    public function __construct(MarketplacePackageService $marketplace)
    {
        $this->marketplace = $marketplace;
    }

    public function index()
    {
        return view('communicationhub::marketplace.index', $this->marketplace->dashboard());
    }

    public function install(CommunicationHubMarketplacePackage $package): RedirectResponse
    {
        $this->marketplace->install($package);
        return back()->with('status', 'Provider package installed successfully.');
    }

    public function enable(CommunicationHubMarketplacePackage $package): RedirectResponse
    {
        $this->marketplace->enable($package);
        return back()->with('status', 'Provider package enabled successfully.');
    }

    public function disable(CommunicationHubMarketplacePackage $package): RedirectResponse
    {
        $this->marketplace->disable($package);
        return back()->with('status', 'Provider package disabled successfully.');
    }

    public function sandbox(CommunicationHubMarketplacePackage $package): RedirectResponse
    {
        $this->marketplace->sandboxTest($package);
        return back()->with('status', 'Sandbox test completed.');
    }
}
