<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pawning\Models\CollateralType;

class CollateralTypeController extends Controller
{
    public function index()
    {
        $types = CollateralType::orderBy('name')->paginate(20);
        return view('pawning::collateral_types.index', compact('types'));
    }

    public function create()
    {
        $type = new CollateralType();
        return view('pawning::collateral_types.form', ['type' => $type, 'action' => route('pawning.collateral-types.store')]);
    }

    public function store(Request $request)
    {
        CollateralType::create($request->only(['business_id','name','code','description','requires_weight','requires_purity','status']));
        return redirect()->route('pawning.collateral-types.index')->with('status', ['success' => 1, 'msg' => 'Collateral type saved successfully']);
    }

    public function edit($id)
    {
        $type = CollateralType::findOrFail($id);
        return view('pawning::collateral_types.form', ['type' => $type, 'action' => route('pawning.collateral-types.update', $id)]);
    }

    public function update(Request $request, $id)
    {
        CollateralType::findOrFail($id)->update($request->only(['business_id','name','code','description','requires_weight','requires_purity','status']));
        return redirect()->route('pawning.collateral-types.index')->with('status', ['success' => 1, 'msg' => 'Collateral type updated successfully']);
    }
}
