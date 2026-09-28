<?php

namespace Modules\ExpensesNew\Services\Tax;

class TaxCalculationService
{
    public function calculateInclusive(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function calculateExclusive(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }

    public function calculateWithholding(array $payload = []): array
    {
        return ['status' => 'ok', 'method' => __METHOD__, 'payload' => $payload];
    }
}
