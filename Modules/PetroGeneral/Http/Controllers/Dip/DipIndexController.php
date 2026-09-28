<?php

namespace Modules\PetroGeneral\Http\Controllers\Dip;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Services\Dip\DipListService;

class DipIndexController extends Controller
{
    protected $dipListService;

    public function __construct(DipListService $dipListService)
    {
        $this->dipListService = $dipListService;
    }

    public function index(Request $request)
    {
        $businessId = (int) $this->resolveBusinessId();

        return view('petrogeneral::dip_management_pg.index', [
            'active_tab' => $request->get('tab', 'readings'),
            'readings' => $this->dipListService->getReadings($businessId),
            'charts' => $this->dipListService->getDipCharts($businessId),
            'resettings' => $this->dipListService->getResettings($businessId),
            'reports' => $this->dipListService->getReports($businessId),

            /*
             * dip_readings and dip_resettings store tank_id only. Without this
             * the Readings and Resettings tabs would print a raw id in the Tank
             * column. Resolved once here rather than per row.
             */
            'pgDipTankNames' => $this->tankNames($businessId),
        ]);
    }

    /*
     * The error page showed `business_id` = 0, so session('business.id') was not
     * set on that request and every list would have come back empty even once the
     * table names were right.
     *
     * This is the resolver PumpController already uses in this module, with the
     * same fallbacks, so the Dip screen agrees with the rest of Petro General.
     */
    /**
     * tank id => tank number, for the Tank column on both tabs.
     */
    private function tankNames($businessId)
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('fuel_tanks')) {
                return [];
            }

            return \Illuminate\Support\Facades\DB::table('fuel_tanks')
                ->where('business_id', $businessId)
                ->pluck('fuel_tank_number', 'id')
                ->toArray();
        } catch (\Throwable $error) {
            // A missing tank list must not take the page down; the raw id shows.
            return [];
        }
    }

    private function resolveBusinessId()
    {
        $businessId = request()->session()->get('business.id');

        if (empty($businessId)) {
            $businessId = request()->session()->get('user.business_id');
        }

        if (empty($businessId)) {
            $businessId = optional(auth()->user())->business_id;
        }

        return $businessId;
    }
}
