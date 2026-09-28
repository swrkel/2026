<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyChair;

class ChairController extends Controller
{
    public function index()
    {
        $records = BeautyChair::orderBy('id', 'desc')->paginate(25);
        return view('beautysaloons::chairs.index', compact('records'));
    }

    public function create()
    {
        return view('beautysaloons::chairs.create');
    }

    public function store(Request $request)
    {
        $data = $request->except(['_token']);
        $data['business_id'] = $data['business_id'] ?? session('business.id') ?? session('business_id');
        BeautyChair::create($data);
        return redirect()->route('beautysaloons.chairs.index')->with('status', __('beautysaloons::beautysaloons.saved_successfully'));
    }

    public function show($id)
    {
        $record = BeautyChair::findOrFail($id);
        return view('beautysaloons::chairs.show', compact('record'));
    }

    public function edit($id)
    {
        $record = BeautyChair::findOrFail($id);
        return view('beautysaloons::chairs.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = BeautyChair::findOrFail($id);
        $record->update($request->except(['_token', '_method']));
        return redirect()->route('beautysaloons.chairs.index')->with('status', __('beautysaloons::beautysaloons.updated_successfully'));
    }

    public function destroy($id)
    {
        $record = BeautyChair::findOrFail($id);
        $record->delete();
        return redirect()->route('beautysaloons.chairs.index')->with('status', __('beautysaloons::beautysaloons.deleted_successfully'));
    }
}
