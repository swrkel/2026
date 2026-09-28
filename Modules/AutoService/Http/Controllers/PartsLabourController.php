<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Services\AutoServicePartsLabourService;
use Modules\AutoService\Services\ProductPartsAdapter;

class PartsLabourController extends AutoServiceBaseController
{
    public function index()
    {
        $q = AutoServiceJob::with(['vehicle','lines','partMovements']);
        if ($this->businessId()) {
            $q->where('business_id', $this->businessId());
        }
        return view('autoservice::parts_labour.index', [
            'jobs' => $q->orderByDesc('id')->paginate(25),
        ]);
    }

    public function edit($id)
    {
        $job = AutoServiceJob::with(['vehicle','lines','partMovements'])->findOrFail($id);
        return view('autoservice::parts_labour.edit', [
            'job' => $job,
            'products' => app(ProductPartsAdapter::class)->listForSelect($this->businessId()),
            'labourLines' => $job->lines->where('line_type', 'labour')->values(),
        ]);
    }

    public function saveParts(Request $request, $id)
    {
        $job = AutoServiceJob::findOrFail($id);
        app(AutoServicePartsLabourService::class)->saveParts($job, $request->input('parts', []), (bool)$request->input('sync_job_lines', true));
        return back()->with('status', 'Parts saved and job totals refreshed.');
    }

    public function saveLabour(Request $request, $id)
    {
        $job = AutoServiceJob::findOrFail($id);
        app(AutoServicePartsLabourService::class)->saveLabour($job, $request->input('labour', []));
        return back()->with('status', 'Labour saved and job totals refreshed.');
    }
}
