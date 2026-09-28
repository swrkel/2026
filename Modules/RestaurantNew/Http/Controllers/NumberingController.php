<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewNumberingSequence;
use Modules\RestaurantNew\Services\CoreSetupService;

class NumberingController extends Controller
{
    public function index(CoreSetupService $service)
    {
        $service->ensureDefaultNumbering();
        return view('restaurantnew::setup.numbering.index', [
            'rows' => $service->scope(RestaurantNewNumberingSequence::query())->orderBy('document_type')->paginate(25),
        ]);
    }

    public function edit($id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewNumberingSequence::query())->findOrFail($id);
        return view('restaurantnew::setup.numbering.form', compact('row'));
    }

    public function update(Request $request, $id, CoreSetupService $service)
    {
        $row = $service->scope(RestaurantNewNumberingSequence::query())->findOrFail($id);
        $data = $request->validate([
            'prefix' => ['nullable', 'string', 'max:50'],
            'next_number' => ['required', 'integer', 'min:1'],
            'padding' => ['required', 'integer', 'min:1', 'max:12'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'reset_yearly' => ['nullable'],
        ]);
        $data['reset_yearly'] = $request->boolean('reset_yearly');
        $row->update($data);
        return redirect()->route('restaurant-new.numbering.index')->with('status', __('restaurantnew::lang.updated_successfully'));
    }
}
