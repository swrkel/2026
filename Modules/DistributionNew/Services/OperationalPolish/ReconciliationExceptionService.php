<?php

namespace Modules\DistributionNew\Services\OperationalPolish;

use Modules\DistributionNew\Entities\OperationalPolish\ReconciliationException;

class ReconciliationExceptionService
{
    public function open(array $data): ReconciliationException
    {
        $expected = (float) ($data['expected_qty'] ?? 0);
        $actual = (float) ($data['actual_qty'] ?? 0);
        return ReconciliationException::create(array_merge($data, [
            'variance_qty' => $actual - $expected,
            'status' => 'open',
            'severity' => $data['severity'] ?? $this->severity($actual - $expected),
        ]));
    }

    public function resolve(ReconciliationException $exception, int $userId, string $note): ReconciliationException
    {
        $exception->update([
            'status' => 'resolved',
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_note' => $note,
        ]);
        return $exception;
    }

    private function severity(float $variance): string
    {
        return abs($variance) > 10 ? 'high' : (abs($variance) > 0 ? 'normal' : 'low');
    }
}
