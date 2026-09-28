<?php

namespace Modules\ExpensesNew\Services\Recurring;

class RecurringExpenseSchedulerService
{
    public function due(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function generate(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function markRun(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }
}
