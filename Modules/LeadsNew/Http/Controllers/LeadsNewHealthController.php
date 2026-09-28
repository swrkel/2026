<?php

namespace Modules\LeadsNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\LeadsNew\Services\LeadsNewV1ValidationService;
use Modules\LeadsNew\Support\LeadsNewReleaseChecklist;

class LeadsNewHealthController extends Controller
{
    public function index(LeadsNewV1ValidationService $validation)
    {
        return view('leadsnew::release.health', [
            'validation' => $validation->run(),
            'checklist' => LeadsNewReleaseChecklist::items(),
        ]);
    }
}
