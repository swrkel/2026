<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LeadsNew\Services\LeadsNewBulkService;

class LeadsNewBulkActionController extends Controller
{
    public function updateStatus(Request $request, LeadsNewBulkService $service)
    {
        $data = $request->validate([
            'lead_ids' => ['required','array'],
            'lead_ids.*' => ['integer'],
            'status' => ['required','string','max:50'],
        ]);

        $count = $service->updateStatus($data['lead_ids'], $data['status'], optional($request->user())->id ?? 0);

        return response()->json(['success' => true, 'updated' => $count]);
    }

    public function handle(Request $request, LeadsNewBulkService $service)
    {
        if ($request->input('action') === 'assign') {
            return $this->assign($request, $service);
        }

        return $this->updateStatus($request, $service);
    }

    public function assign(Request $request, LeadsNewBulkService $service)
    {
        $data = $request->validate([
            'lead_ids' => ['required','array'],
            'lead_ids.*' => ['integer'],
            'assigned_to' => ['required','integer'],
        ]);

        $count = $service->assign($data['lead_ids'], $data['assigned_to'], optional($request->user())->id ?? 0);

        return response()->json(['success' => true, 'updated' => $count]);
    }
}
