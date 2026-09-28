<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSConfigurationCenterService;

class ConfigurationCenterController extends Controller
{
    protected POSConfigurationCenterService $configurationService;

    public function __construct(POSConfigurationCenterService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $sections = $this->configurationService->sections($businessId);

        return view('pos::configuration.index', compact('sections'));
    }

    public function section(Request $request, string $section)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $this->configurationService->section($businessId, $section);

        return view('pos::configuration.section', compact('section', 'data'));
    }
}
