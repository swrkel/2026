<?php

namespace Modules\LeadsNew\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\LeadsNew\Models\LeadsNewLead;

class LeadsNewApiController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'data' => LeadsNewLead::query()->latest('id')->limit(50)->get(),
        ]);
    }

    public function show($id)
    {
        return response()->json(['data' => LeadsNewLead::query()->findOrFail($id)]);
    }
}
