<?php

namespace Modules\PetroGeneral\Http\Controllers\Pumper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Services\Pumper\PumperListService;

class PumperIndexController extends Controller
{
    protected $pumperListService;

    public function __construct(PumperListService $pumperListService)
    {
        $this->pumperListService = $pumperListService;
    }

    public function index(Request $request)
    {
        $businessId = (int) session('business.id');

        return view('petrogeneral::pumper_management.index', [
            'active_tab' => $request->get('tab', 'pumpers'),
            'pumpers' => $this->pumperListService->getPumpers($businessId),
            'assignments' => $this->pumperListService->getAssignments($businessId),
            'shifts' => $this->pumperListService->getRecentShifts($businessId),
            'settings' => $this->pumperListService->getSettings($businessId),
        ]);
    }
}
