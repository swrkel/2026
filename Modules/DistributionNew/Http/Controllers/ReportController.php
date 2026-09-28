<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class ReportController extends Controller
{
    public function index()
    {
        return view('distributionnew::reports.index');
    }

    public function create()
    {
        return view('distributionnew::Report.create');
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('status', 'Saved successfully.');
    }

    public function show($id)
    {
        return view('distributionnew::Report.show', compact('id'));
    }

    public function complete($id)
    {
        if ('Report' === 'Loading') { app(\Modules\DistributionNew\Services\DisnewLoadingService::class)->complete((int) $id); }
        return redirect()->back()->with('status', 'Completed successfully.');
    }

    public function saveSmsOfficers(Request $request)
    {
        $businessId = DisnewTenantUtil::businessId();
        DB::table('disnew_sms_officers')->where('business_id', $businessId)->delete();
        foreach ((array) $request->input('officers', []) as $officer) {
            if (!empty($officer['mobile'])) {
                DB::table('disnew_sms_officers')->insert(['business_id' => $businessId, 'name' => $officer['name'] ?? null, 'mobile' => $officer['mobile'], 'event_mask' => json_encode($officer['events'] ?? []), 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        return redirect()->back()->with('status', 'SMS officers saved.');
    }
}
