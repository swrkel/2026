<?php

namespace Modules\ExpensesNew\Services\Control;

class ExpenseControlDecisionService
{
    public function allow(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function warn(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function requireApproval(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function block(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }
}
