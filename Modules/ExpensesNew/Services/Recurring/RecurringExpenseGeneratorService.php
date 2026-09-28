<?php

namespace Modules\ExpensesNew\Services\Recurring;

class RecurringExpenseGeneratorService
{
    public function makeExpense(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function copyItems(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function copyAttachments(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }
}
