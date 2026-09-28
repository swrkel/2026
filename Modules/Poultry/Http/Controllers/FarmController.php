<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Farm;
use Modules\Poultry\Entities\Shared\BusinessLocation;

class FarmController extends PoultryBaseController
{
    public function index()
    {
        $this->authorizePermission('poultry.master.view');

        $rows = Farm::query()->forBusiness($this->businessId())
            ->withCount('houses')
            ->orderBy('name')
            ->paginate(50);

        return view('poultry::masters.farms.index', compact('rows'));
    }

    public function create()
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.farms.create', [
            'locations' => BusinessLocation::dropdown($this->businessId()),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.master.manage');

        Farm::create($request->validate($this->rules()));

        return redirect()->route('poultry.farms.index')
            ->with('status', ['success' => 1, 'msg' => 'Farm saved.']);
    }

    public function edit($id)
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.farms.edit', [
            'row'       => Farm::query()->forBusiness($this->businessId())->findOrFail($id),
            'locations' => BusinessLocation::dropdown($this->businessId()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = Farm::query()->forBusiness($this->businessId())->findOrFail($id);
        $row->fill($request->validate($this->rules($id)))->save();

        return redirect()->route('poultry.farms.index')
            ->with('status', ['success' => 1, 'msg' => 'Farm updated.']);
    }

    public function destroy($id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = Farm::query()->forBusiness($this->businessId())->findOrFail($id);

        // Deactivate rather than delete - batches still reference this farm.
        $row->is_active = 0;
        $row->save();

        return $this->ok('Farm deactivated. Historical batches still reference it, so it was not deleted.');
    }

    protected function rules($id = null)
    {
        return [
            'name'            => 'required|string|max:255',
            'code'            => 'nullable|string|max:32',
            'location_id'     => 'nullable|integer',
            'address'         => 'nullable|string',
            'city'            => 'nullable|string|max:100',
            'manager_user_id' => 'nullable|integer',
            'is_active'       => 'nullable|boolean',
            'notes'           => 'nullable|string',
        ];
    }
}
