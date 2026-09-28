<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Batch;
use Modules\Poultry\Entities\HatchSet;
use Modules\Poultry\Entities\Shared\BusinessLocation;
use Modules\Poultry\Entities\Shared\Contact;
use Modules\Poultry\Entities\Shared\Product;
use Modules\Poultry\Entities\Setting;
use Modules\Poultry\Services\HatcheryService;

class HatcheryController extends PoultryBaseController
{
    protected $hatchery;

    public function __construct(HatcheryService $hatchery)
    {
        $this->hatchery = $hatchery;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('poultry.hatchery.view');

        $sets = HatchSet::query()->forBusiness($this->businessId())
            ->with(['sourceBatch', 'supplier'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->orderByDesc('set_date')
            ->paginate(50);

        return view('poultry::hatchery.index', [
            'sets'     => $sets,
            'statuses' => HatchSet::STATUSES,
        ]);
    }

    public function create()
    {
        $this->authorizePermission('poultry.hatchery.create');

        $businessId    = $this->businessId();
        $chickCategories = (array) Setting::get('chick_category_ids', []);

        return view('poultry::hatchery.create', [
            'breederBatches' => Batch::dropdown('breeder', true, $businessId),
            'suppliers'      => Contact::dropdown('supplier', $businessId),
            'items'          => Product::variationDropdown($chickCategories, $businessId),
            'locations'      => BusinessLocation::dropdown($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('poultry.hatchery.create');

        $data = $request->validate([
            'source_batch_id'     => 'nullable|integer',
            'supplier_contact_id' => 'nullable|integer',
            'set_code'            => 'nullable|string|max:64',
            'set_date'            => 'required|date',
            'setter_no'           => 'nullable|string|max:32',
            'eggs_set'            => 'required|integer|min:1',
            'product_id'          => 'nullable|integer',
            'variation_id'        => 'nullable|integer',
            'notes'               => 'nullable|string',
        ]);

        $set = $this->hatchery->set($data);

        return redirect()->route('poultry.hatchery.show', $set->id)
            ->with('status', ['success' => 1, 'msg' => 'Hatch set '.$set->set_code.' recorded.']);
    }

    public function show($id)
    {
        $this->authorizePermission('poultry.hatchery.view');

        $set = HatchSet::query()->forBusiness($this->businessId())
            ->with(['sourceBatch', 'supplier'])
            ->findOrFail($id);

        return view('poultry::hatchery.show', [
            'set'       => $set,
            'locations' => BusinessLocation::dropdown($this->businessId()),
        ]);
    }

    public function candle(Request $request, $id)
    {
        $this->authorizePermission('poultry.hatchery.create');

        $set = HatchSet::query()->forBusiness($this->businessId())->findOrFail($id);

        $data = $request->validate([
            'candling_date' => 'required|date|after_or_equal:'.$set->set_date->toDateString(),
            'fertile_eggs'  => 'required|integer|min:0|max:'.$set->eggs_set,
            'clear_eggs'    => 'nullable|integer|min:0',
        ]);

        $set = $this->hatchery->candle($set, $data);

        return $this->ok('Candling recorded. Fertility '.$set->fertility_pct.'%.', ['set' => $set]);
    }

    public function hatch(Request $request, $id)
    {
        $this->authorizePermission('poultry.hatchery.create');

        $set = HatchSet::query()->forBusiness($this->businessId())->findOrFail($id);

        $data = $request->validate([
            'hatch_date'      => 'required|date|after_or_equal:'.$set->set_date->toDateString(),
            'chicks_hatched'  => 'required|integer|min:0|max:'.$set->eggs_set,
            'saleable_chicks' => 'nullable|integer|min:0',
            'culled_chicks'   => 'nullable|integer|min:0',
            'dead_in_shell'   => 'nullable|integer|min:0',
            'location_id'     => 'nullable|integer',
        ]);

        try {
            $set = $this->hatchery->hatch($set, $data);
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('Could not record the hatch: '.$e->getMessage());
        }

        return $this->ok(
            'Hatch recorded. Hatchability '.$set->hatchability_pct.'%, hatch of set '.$set->hatch_of_set_pct.'%.',
            ['set' => $set]
        );
    }
}
