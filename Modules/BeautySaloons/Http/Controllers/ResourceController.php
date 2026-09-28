<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyResource;

class ResourceController extends Controller
{
    public function index()
    {
        $records = BeautyResource::orderBy('id', 'desc')->paginate(25);
        return view('beautysaloons::resources.index', compact('records'));
    }

    public function create()
    {
        return view('beautysaloons::resources.create');
    }

    public function store(Request $request)
    {
        $data = $request->except(['_token']);
        $data['business_id'] = $data['business_id'] ?? session('business.id') ?? session('business_id');
        BeautyResource::create($data);
        return redirect()->route('beautysaloons.resources.index')->with('status', __('beautysaloons::beautysaloons.saved_successfully'));
    }

    public function show($id)
    {
        $record = BeautyResource::findOrFail($id);
        return view('beautysaloons::resources.show', compact('record'));
    }

    public function edit($id)
    {
        $record = BeautyResource::findOrFail($id);
        return view('beautysaloons::resources.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = BeautyResource::findOrFail($id);
        $record->update($request->except(['_token', '_method']));
        return redirect()->route('beautysaloons.resources.index')->with('status', __('beautysaloons::beautysaloons.updated_successfully'));
    }

    public function destroy($id)
    {
        $record = BeautyResource::findOrFail($id);
        $record->delete();
        return redirect()->route('beautysaloons.resources.index')->with('status', __('beautysaloons::beautysaloons.deleted_successfully'));
    }
}
