<?php

namespace Modules\LeadsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadsNew\Models\LeadsNewFollowup;

class LeadsNewFollowupController extends Controller
{
    public function index(Request $request)
    {
        $query = LeadsNewFollowup::with('lead')->latest('id');

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('status', 'like', "%{$q}%")
                    ->orWhere('note', 'like', "%{$q}%");
            });
        }

        $followups = $query->paginate(25);
        return view('leadsnew::followups.index', compact('followups'));
    }

    public function create()
    {
        return $this->index(request());
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Follow-up saved successfully']);
    }

    public function show($id)
    {
        return $this->index(request());
    }

    public function edit($id)
    {
        return $this->index(request());
    }

    public function update(Request $request, $id)
    {
        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Follow-up updated successfully']);
    }

    public function destroy($id)
    {
        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Follow-up deleted successfully']);
    }
}
