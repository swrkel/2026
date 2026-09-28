<?php

namespace Modules\FinanceReports\Services\Enterprise;

use Illuminate\Support\Facades\Schema;

class EnterpriseDataHubService
{
    public function modules(): array
    {
        return [
            ['key' => 'finance', 'name' => 'Finance', 'status' => 'active', 'source' => 'accounting tables', 'mode' => 'read-only'],
            ['key' => 'petropd', 'name' => 'PetroPD', 'status' => $this->tableExists('pump_operator_payments') ? 'available' : 'future-ready', 'source' => 'settlement and fuel sales summaries', 'mode' => 'adapter'],
            ['key' => 'distribution', 'name' => 'Distribution', 'status' => $this->tableExists('distribution_orders') ? 'available' : 'future-ready', 'source' => 'sales and delivery summaries', 'mode' => 'adapter'],
            ['key' => 'customers', 'name' => 'Customers', 'status' => $this->tableExists('contacts') ? 'available' : 'future-ready', 'source' => 'customer ledger and collection behaviour', 'mode' => 'adapter'],
            ['key' => 'membership', 'name' => 'Membership', 'status' => $this->tableExists('memberships') ? 'available' : 'future-ready', 'source' => 'member revenue and outstanding fees', 'mode' => 'adapter'],
            ['key' => 'myhealth', 'name' => 'MyHealth', 'status' => $this->tableExists('myhealth_members') ? 'available' : 'future-ready', 'source' => 'clinic revenue and service profitability', 'mode' => 'adapter'],
            ['key' => 'inventory', 'name' => 'Inventory', 'status' => $this->tableExists('variation_location_details') ? 'available' : 'future-ready', 'source' => 'stock valuation and movement', 'mode' => 'adapter'],
        ];
    }

    public function summary(): array
    {
        $modules = $this->modules();
        return [
            'modules' => $modules,
            'active' => collect($modules)->whereIn('status', ['active', 'available'])->count(),
            'future_ready' => collect($modules)->where('status', 'future-ready')->count(),
            'principle' => 'Finance Reports reads through adapters only and never posts, edits or deletes operational data.',
        ];
    }

    private function tableExists(string $table): bool
    {
        try { return Schema::hasTable($table); } catch (\Throwable $e) { return false; }
    }
}
