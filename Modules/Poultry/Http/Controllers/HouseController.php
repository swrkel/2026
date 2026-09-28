<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Farm;
use Modules\Poultry\Entities\House;

class HouseController extends PoultryBaseController
{
    public function index(Request $request)
    {
        $this->authorizePermission('poultry.master.view');

        $rows = House::query()->forBusiness($this->businessId())
            ->with(['farm', 'activeBatch'])
            ->when($request->filled('farm_id'), function ($q) use ($request) {
                $q->where('farm_id', $request->input('farm_id'));
            })
            ->orderBy('name')
            ->paginate(50);

        return view('poultry::masters.houses.index', [
            'rows'  => $rows,
            'farms' => Farm::dropdown($this->businessId()),
        ]);
    }

    public function create()
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.houses.create', [
            'farms' => Farm::dropdown($this->businessId()),
            'types' => House::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.master.manage');

        House::create($request->validate($this->rules()));

        return redirect()->route('poultry.houses.index')
            ->with('status', ['success' => 1, 'msg' => 'House saved.']);
    }

    public function edit($id)
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.houses.edit', [
            'row'   => House::query()->forBusiness($this->businessId())->findOrFail($id),
            'farms' => Farm::dropdown($this->businessId()),
            'types' => House::TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = House::query()->forBusiness($this->businessId())->findOrFail($id);
        $row->fill($request->validate($this->rules($id)))->save();

        return redirect()->route('poultry.houses.index')
            ->with('status', ['success' => 1, 'msg' => 'House updated.']);
    }

    public function destroy($id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = House::query()->forBusiness($this->businessId())->findOrFail($id);

        if ($row->activeBatch) {
            return $this->fail('That house currently holds an active batch and cannot be deactivated.');
        }

        $row->is_active = 0;
        $row->save();

        return $this->ok('House deactivated.');
    }

    protected function rules($id = null)
    {
        return [
            'farm_id'        => 'required|integer',
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:32',
            'housing_type'   => 'required|in:deep_litter,cage,free_range,slatted,breeder',
            'capacity'       => 'required|integer|min:0',
            'floor_area_sqm' => 'nullable|numeric|min:0',
            'is_active'      => 'nullable|boolean',
        ];
    }
}
