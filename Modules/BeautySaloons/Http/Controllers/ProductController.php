<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return view('beautysaloons::products.index');
    }

    public function create()
    {
        return view('beautysaloons::products.create');
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', __('beautysaloons::beautysaloons.saved_successfully'));
    }

    public function show($id)
    {
        return view('beautysaloons::products.show', compact('id'));
    }

    public function edit($id)
    {
        return view('beautysaloons::products.edit', compact('id'));
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
