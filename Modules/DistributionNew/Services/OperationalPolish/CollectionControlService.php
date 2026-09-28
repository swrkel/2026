<?php

namespace Modules\DistributionNew\Services\OperationalPolish;

use Modules\DistributionNew\Entities\OperationalPolish\CollectionControl;

class CollectionControlService
{
    public function record(array $data): CollectionControl
    {
        $expected = (float) ($data['expected_amount'] ?? 0);
        $collected = (float) ($data['collected_amount'] ?? 0);
        return CollectionControl::create(array_merge($data, [
            'short_excess_amount' => $collected - $expected,
            'status' => $data['status'] ?? 'open',
        ]));
    }

    public function approve(CollectionControl $control, int $userId, ?string $remarks = null): CollectionControl
    {
        $control->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'remarks' => $remarks ?? $control->remarks,
        ]);
        return $control;
    }
}
