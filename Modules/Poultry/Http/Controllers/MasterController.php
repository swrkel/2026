<?php

namespace Modules\Poultry\Http\Controllers;

use Modules\Poultry\Entities\Breed;
use Modules\Poultry\Entities\EggGrade;
use Modules\Poultry\Entities\Farm;
use Modules\Poultry\Entities\House;
use Modules\Poultry\Entities\VaccinationSchedule;

/** Landing page for the master data screens. */
class MasterController extends PoultryBaseController
{
    public function index()
    {
        $this->authorizePermission('poultry.master.view');

        $businessId = $this->businessId();

        return view('poultry::masters.index', [
            'counts' => [
                'farms'     => Farm::query()->forBusiness($businessId)->count(),
                'houses'    => House::query()->forBusiness($businessId)->count(),
                'breeds'    => Breed::query()->forBusiness($businessId)->count(),
                'grades'    => EggGrade::query()->forBusiness($businessId)->count(),
                'schedules' => VaccinationSchedule::query()->forBusiness($businessId)->count(),
            ],
        ]);
    }
}
