<?php

namespace Modules\MyHealthMembers\Services\Radiology;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthRadiologyAttachment;
use Modules\MyHealthMembers\Entities\MyHealthRadiologyReport;
use Modules\MyHealthMembers\Entities\MyHealthRadiologyRequest;

class MyHealthRadiologyService
{
    public function dashboardCounts(): array
    {
        $today = Carbon::today();

        return [
            'requests_today' => MyHealthRadiologyRequest::whereDate('created_at', $today)->count(),
            'scheduled_today' => MyHealthRadiologyRequest::whereDate('scheduled_at', $today)->count(),
            'pending' => MyHealthRadiologyRequest::whereIn('status', ['requested', 'scheduled'])->count(),
            'performed' => MyHealthRadiologyRequest::where('status', 'performed')->count(),
            'awaiting_report' => MyHealthRadiologyRequest::whereIn('status', ['performed', 'reporting'])->count(),
            'released' => MyHealthRadiologyRequest::where('status', 'released')->count(),
            'critical' => MyHealthRadiologyReport::where('critical_finding', true)->whereIn('status', ['reported', 'verified', 'approved', 'released'])->count(),
        ];
    }

    public function createRequest(array $data): MyHealthRadiologyRequest
    {
        $data['request_no'] = $data['request_no'] ?? $this->nextRequestNumber();
        $data['status'] = $data['status'] ?? 'requested';
        $data['created_by'] = Auth::id();
        $data['business_id'] = $data['business_id'] ?? session('business.id') ?? session('business_id');
        $data['location_id'] = $data['location_id'] ?? session('business_location_id');

        return MyHealthRadiologyRequest::create($data);
    }

    public function updateStage(MyHealthRadiologyRequest $request, string $status): MyHealthRadiologyRequest
    {
        $allowed = ['requested', 'scheduled', 'performed', 'reporting', 'verified', 'approved', 'released', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            $status = 'requested';
        }

        $request->status = $status;
        $request->updated_by = Auth::id();

        $timestampColumn = [
            'scheduled' => 'scheduled_at',
            'performed' => 'performed_at',
            'reporting' => 'reported_at',
            'verified' => 'verified_at',
            'approved' => 'approved_at',
            'released' => 'released_at',
        ][$status] ?? null;

        if ($timestampColumn && empty($request->{$timestampColumn})) {
            $request->{$timestampColumn} = now();
        }

        $request->save();

        return $request;
    }

    public function saveReport(array $data): MyHealthRadiologyReport
    {
        return DB::transaction(function () use ($data) {
            $radiologyRequest = MyHealthRadiologyRequest::findOrFail($data['radiology_request_id']);

            $data['member_id'] = $data['member_id'] ?? $radiologyRequest->member_id;
            $data['business_id'] = $data['business_id'] ?? $radiologyRequest->business_id;
            $data['report_no'] = $data['report_no'] ?? $this->nextReportNumber();
            $data['status'] = $data['status'] ?? 'reported';
            $data['critical_finding'] = ! empty($data['critical_finding']);
            $data['reported_by'] = $data['reported_by'] ?? Auth::id();
            $data['created_by'] = Auth::id();
            $data['reported_at'] = $data['reported_at'] ?? now();

            $report = MyHealthRadiologyReport::create($data);

            $radiologyRequest->status = $data['status'];
            $radiologyRequest->radiologist_id = $data['reported_by'];
            $radiologyRequest->reported_at = now();
            $radiologyRequest->save();

            return $report;
        });
    }

    public function attachFile(MyHealthRadiologyRequest $request, array $data): MyHealthRadiologyAttachment
    {
        $data['radiology_request_id'] = $request->id;
        $data['member_id'] = $request->member_id;
        $data['business_id'] = $request->business_id;
        $data['uploaded_by'] = Auth::id();

        return MyHealthRadiologyAttachment::create($data);
    }

    public function nextRequestNumber(): string
    {
        $next = (int) MyHealthRadiologyRequest::max('id') + 1;
        return 'RAD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function nextReportNumber(): string
    {
        $next = (int) MyHealthRadiologyReport::max('id') + 1;
        return 'RADR-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
