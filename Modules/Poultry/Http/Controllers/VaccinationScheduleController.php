<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Breed;
use Modules\Poultry\Entities\Setting;
use Modules\Poultry\Entities\Shared\Product;
use Modules\Poultry\Entities\VaccinationSchedule;

class VaccinationScheduleController extends PoultryBaseController
{
    public function index()
    {
        $this->authorizePermission('poultry.master.view');

        $rows = VaccinationSchedule::query()->forBusiness($this->businessId())
            ->with('breed')
            ->orderBy('bird_type')->orderBy('age_days')
            ->paginate(100);

        return view('poultry::masters.vaccination_schedules.index', compact('rows'));
    }

    public function create()
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.vaccination_schedules.create', $this->options());
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.master.manage');

        VaccinationSchedule::create($request->validate($this->rules()));

        return redirect()->route('poultry.vaccination-schedules.index')
            ->with('status', ['success' => 1, 'msg' => 'Schedule row saved.']);
    }

    public function edit($id)
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.vaccination_schedules.edit', $this->options() + [
            'row' => VaccinationSchedule::query()->forBusiness($this->businessId())->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = VaccinationSchedule::query()->forBusiness($this->businessId())->findOrFail($id);
        $row->fill($request->validate($this->rules($id)))->save();

        return redirect()->route('poultry.vaccination-schedules.index')
            ->with('status', ['success' => 1, 'msg' => 'Schedule row updated.']);
    }

    public function destroy($id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = VaccinationSchedule::query()->forBusiness($this->businessId())->findOrFail($id);
        $row->is_active = 0;
        $row->save();

        return $this->ok('Schedule row deactivated.');
    }

    protected function options()
    {
        $vaccineCategories = (array) Setting::get('vaccine_category_ids', []);

        return [
            'breeds' => Breed::dropdown(null, $this->businessId()),
            'items'  => Product::variationDropdown($vaccineCategories, $this->businessId()),
            'routes' => VaccinationSchedule::ROUTES,
            'types'  => ['all' => 'All types'] + \Modules\Poultry\Entities\Batch::BIRD_TYPES,
        ];
    }

    protected function rules($id = null)
    {
        return [
            'name'         => 'required|string|max:255',
            'disease'      => 'nullable|string|max:255',
            'bird_type'    => 'required|in:broiler,layer,pullet,breeder,all',
            'breed_id'     => 'nullable|integer',
            'product_id'   => 'nullable|integer',
            'age_days'     => 'required|integer|min:0|max:600',
            'route'        => 'required|in:drinking_water,eye_drop,spray,injection_sc,injection_im,wing_web,beak_dip',
            'dose'         => 'nullable|string|max:100',
            'is_mandatory' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
        ];
    }
}
