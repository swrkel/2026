<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Breed;

class BreedController extends PoultryBaseController
{
    public function index()
    {
        $this->authorizePermission('poultry.master.view');

        $rows = Breed::query()->forBusiness($this->businessId())
            ->orderBy('bird_type')->orderBy('name')
            ->paginate(50);

        return view('poultry::masters.breeds.index', compact('rows'));
    }

    public function create()
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.breeds.create', ['types' => Breed::BIRD_TYPES]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.master.manage');

        Breed::create($this->prepare($request));

        return redirect()->route('poultry.breeds.index')
            ->with('status', ['success' => 1, 'msg' => 'Breed saved.']);
    }

    public function edit($id)
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.breeds.edit', [
            'row'   => Breed::query()->forBusiness($this->businessId())->findOrFail($id),
            'types' => Breed::BIRD_TYPES,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = Breed::query()->forBusiness($this->businessId())->findOrFail($id);
        $row->fill($this->prepare($request))->save();

        return redirect()->route('poultry.breeds.index')
            ->with('status', ['success' => 1, 'msg' => 'Breed updated.']);
    }

    public function destroy($id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = Breed::query()->forBusiness($this->businessId())->findOrFail($id);
        $row->is_active = 0;
        $row->save();

        return $this->ok('Breed deactivated.');
    }

    /**
     * The breed standard is submitted as JSON. Validate that it parses before
     * storing, so a malformed paste fails here rather than silently returning
     * null targets on every performance report afterwards.
     */
    protected function prepare(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'bird_type'      => 'required|in:broiler,layer,breeder,dual_purpose',
            'standard_curve' => 'nullable|string',
            'is_active'      => 'nullable|boolean',
        ]);

        if (! empty($data['standard_curve'])) {
            json_decode($data['standard_curve'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                abort(422, 'The breed standard must be valid JSON. Expected shape: '
                    .'{"weights":{"7":180},"hen_day":{"25":88},"target_fcr":1.55}');
            }
        }

        return $data;
    }
}
