<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        return view('beautysaloons::rooms.index');
    }

    public function create()
    {
        return view('beautysaloons::rooms.create');
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('beautysaloons::beautysaloons.saved_successfully'));
    }

    public function show($id)
    {
        return view('beautysaloons::rooms.show', compact('id'));
    }

    public function edit($id)
    {
        return view('beautysaloons::rooms.edit', compact('id'));
    }

    public function update(Request $request, $id)
    {
        return redirect()->back()->with('status', __('beautysaloons::beautysaloons.updated_successfully'));
    }

    public function destroy($id)
    {
        return redirect()->back()->with('status', __('beautysaloons::beautysaloons.deleted_successfully'));
    }
}
