<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\EggGrade;
use Modules\Poultry\Entities\Setting;
use Modules\Poultry\Entities\Shared\Product;

/**
 * Egg grades, and their mapping to sellable variations in the shared catalogue.
 * That mapping is what lets collected eggs become stock the POS and
 * Distribution modules can sell without any sales code in this module.
 */
class EggGradeController extends PoultryBaseController
{
    public function index()
    {
        $this->authorizePermission('poultry.master.view');

        $rows = EggGrade::query()->forBusiness($this->businessId())
            ->with('variation.product')
            ->ordered()
            ->paginate(50);

        return view('poultry::masters.egg_grades.index', compact('rows'));
    }

    public function create()
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.egg_grades.create', $this->options());
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.master.manage');

        EggGrade::create($this->prepare($request));

        return redirect()->route('poultry.egg-grades.index')
            ->with('status', ['success' => 1, 'msg' => 'Grade saved.']);
    }

    public function edit($id)
    {
        $this->authorizePermission('poultry.master.manage');

        return view('poultry::masters.egg_grades.edit', $this->options() + [
            'row' => EggGrade::query()->forBusiness($this->businessId())->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = EggGrade::query()->forBusiness($this->businessId())->findOrFail($id);
        $row->fill($this->prepare($request))->save();

        return redirect()->route('poultry.egg-grades.index')
            ->with('status', ['success' => 1, 'msg' => 'Grade updated.']);
    }

    public function destroy($id)
    {
        $this->authorizePermission('poultry.master.manage');

        $row = EggGrade::query()->forBusiness($this->businessId())->findOrFail($id);

        if ($row->collections()->exists()) {
            $row->is_saleable = 0;
            $row->save();

            return $this->ok('Grade has collection history, so it was marked not saleable rather than deleted.');
        }

        $row->delete();

        return $this->ok('Grade deleted.');
    }

    protected function options()
    {
        $eggCategories = (array) Setting::get('egg_category_ids', []);

        return ['items' => Product::variationDropdown($eggCategories, $this->businessId())];
    }

    /**
     * variation_id arrives from the select; product_id is derived from it so
     * the two can never drift apart.
     */
    protected function prepare(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:64',
            'code'         => 'nullable|string|max:16',
            'min_weight_g' => 'nullable|numeric|min:0',
            'max_weight_g' => 'nullable|numeric|min:0|gte:min_weight_g',
            'variation_id' => 'nullable|integer',
            'is_saleable'  => 'nullable|boolean',
            'sort_order'   => 'nullable|integer|min:0',
        ]);

        if (! empty($data['variation_id'])) {
            $variation = \Modules\Poultry\Entities\Shared\Variation::find($data['variation_id']);
            $data['product_id'] = $variation ? $variation->product_id : null;
        } else {
            $data['product_id'] = null;
        }

        return $data;
    }
}
