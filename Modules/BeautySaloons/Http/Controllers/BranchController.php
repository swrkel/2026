<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyBranch;

class BranchController extends Controller
{
    public function index()
    {
        $records = BeautyBranch::orderBy('id', 'desc')->paginate(25);
        return view('beautysaloons::branches.index', compact('records'));
    }

    public function create()
    {
        return view('beautysaloons::branches.create');
    }

    public function store(Request $request)
    {
        $data = $request->except(['_token']);
        $data['business_id'] = $data['business_id'] ?? session('business.id') ?? session('business_id');
        BeautyBranch::create($data);
        return redirect()->route('beautysaloons.branches.index')->with('status', __('beautysaloons::beautysaloons.saved_successfully'));
    }

    public function show($id)
    {
        $record = BeautyBranch::findOrFail($id);
        return view('beautysaloons::branches.show', compact('record'));
    }

    public function edit($id)
    {
        $record = BeautyBranch::findOrFail($id);
        return view('beautysaloons::branches.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = BeautyBranch::findOrFail($id);
        $record->update($request->except(['_token', '_method']));
        return redirect()->route('beautysaloons.branches.index')->with('status', __('beautysaloons::beautysaloons.updated_successfully'));
    }

    public function destroy($id)
    {
        $record = BeautyBranch::findOrFail($id);
        $record->delete();
        return redirect()->route('beautysaloons.branches.index')->with('status', __('beautysaloons::beautysaloons.deleted_successfully'));
    }
}
