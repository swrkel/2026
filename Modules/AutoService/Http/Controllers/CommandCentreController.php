<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Services\AutoServiceCommandCentreService;

class CommandCentreController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $service = new AutoServiceCommandCentreService(
            $this->autoServiceTenantConnection(),
            $this->businessId(),
            $this->locationId()
        );

        $data = $service->summary($request->only(['delay_hours']));
        $data['page_title'] = 'Workshop Command Centre';

        return view('autoservice::command_centre.index', $data);
    }

    public function live(Request $request)
    {
        $service = new AutoServiceCommandCentreService(
            $this->autoServiceTenantConnection(),
            $this->businessId(),
            $this->locationId()
        );

        return response()->json($service->summary($request->only(['delay_hours'])));
    }
}
