<?php

namespace Modules\ExpensesNew\Services\Integration;

class ExpenseModuleCostPoster
{
    public function __construct(private ExpenseIntegrationBridgeService $bridge) {}
    public function fromModule(string $module, array $data): array
    {
        $data['source_module'] = $module;
        return $this->bridge->postCost($data);
    }
}
