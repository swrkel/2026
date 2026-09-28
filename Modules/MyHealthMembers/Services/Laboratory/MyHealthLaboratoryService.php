<?php

namespace Modules\MyHealthMembers\Services\Laboratory;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\MyHealthMembers\Entities\MyHealthLabResult;
use Modules\MyHealthMembers\Entities\MyHealthLabSample;
use Modules\MyHealthMembers\Entities\MyHealthLabTestCatalogue;

class MyHealthLaboratoryService
{
    public function dashboardCounts(): array
    {
        return [
            'tests_today' => MyHealthLabSample::whereDate('created_at', today())->count(),
            'samples_collected' => MyHealthLabSample::where('status', 'collected')->count(),
            'in_progress' => MyHealthLabSample::whereIn('status', ['received', 'processing'])->count(),
            'awaiting_verification' => MyHealthLabResult::where('status', 'entered')->count(),
            'released' => MyHealthLabSample::where('status', 'released')->count(),
            'critical' => MyHealthLabResult::where('is_critical', 1)->count(),
        ];
    }

    public function nextSampleNo(): string
    {
        $last = MyHealthLabSample::max('id') ?? 0;
        return 'LAB-' . date('Ymd') . '-' . str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    public function createTest(array $data): MyHealthLabTestCatalogue
    {
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? 'active';
        return MyHealthLabTestCatalogue::create($data);
    }

    public function collectSample(array $data): MyHealthLabSample
    {
        $data['sample_no'] = $data['sample_no'] ?? $this->nextSampleNo();
        $data['barcode'] = $data['barcode'] ?? strtoupper(Str::random(12));
        $data['status'] = $data['status'] ?? 'collected';
        $data['collector_id'] = Auth::id();
        $data['created_by'] = Auth::id();
        $data['collected_at'] = $data['collected_at'] ?? now();

        return MyHealthLabSample::create($data);
    }

    public function updateSampleStage(MyHealthLabSample $sample, string $status): MyHealthLabSample
    {
        $allowed = ['requested', 'collected', 'received', 'processing', 'verified', 'approved', 'released', 'rejected'];
        if (! in_array($status, $allowed, true)) {
            $status = $sample->status;
        }

        $sample->status = $status;
        $sample->updated_by = Auth::id();

        $fieldMap = [
            'received' => 'received_at',
            'processing' => 'processed_at',
            'verified' => 'verified_at',
            'approved' => 'approved_at',
            'released' => 'released_at',
        ];

        if (isset($fieldMap[$status]) && empty($sample->{$fieldMap[$status]})) {
            $sample->{$fieldMap[$status]} = now();
        }

        $sample->save();
        return $sample;
    }

    public function enterResult(array $data): MyHealthLabResult
    {
        $data['entered_by'] = Auth::id();
        $data['entered_at'] = $data['entered_at'] ?? now();
        $data['status'] = $data['status'] ?? 'entered';
        $data['is_abnormal'] = ! empty($data['is_abnormal']);
        $data['is_critical'] = ! empty($data['is_critical']);

        return MyHealthLabResult::create($data);
    }
}
