<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EnterpriseIntegrationController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $schema = Schema::connection($this->autoServiceTenantConnection());
        $businessId = $this->businessId();
        $locationId = $this->locationId();

        $integrationChecks = collect([
            ['module' => 'Customers', 'table' => 'contacts', 'purpose' => 'Customer profile, ledger identity and customer history link'],
            ['module' => 'Products / Inventory', 'table' => 'products', 'purpose' => 'Parts/accessories master and product dropdown source'],
            ['module' => 'Inventory Variation Locations', 'table' => 'variation_location_details', 'purpose' => 'Location-wise stock availability for service parts'],
            ['module' => 'Suppliers', 'table' => 'contacts', 'purpose' => 'Supplier-linked parts purchasing and warranty claims'],
            ['module' => 'Finance / Accounts', 'table' => 'transactions', 'purpose' => 'Invoice, payment and account posting bridge'],
            ['module' => 'Business Locations', 'table' => 'business_locations', 'purpose' => 'Multi-business / multi-location workshop filtering'],
            ['module' => 'Communication Hub', 'table' => 'communication_hub_messages', 'purpose' => 'SMS / Email / WhatsApp / notification trigger bridge'],
            ['module' => 'Documents', 'table' => 'documents', 'purpose' => 'Vehicle photos, job cards, estimates and warranty evidence'],
            ['module' => 'HR / Technicians', 'table' => 'users', 'purpose' => 'Technician, service advisor and approval user mapping'],
        ])->map(function ($row) use ($schema) {
            $exists = false;
            try { $exists = $schema->hasTable($row['table']); } catch (\Throwable $e) { $exists = false; }
            return $row + ['available' => $exists, 'status' => $exists ? 'Ready' : 'Optional / Not Found'];
        });

        $postingSummary = [
            'jobs_ready_for_invoice' => $this->safeCount('auto_service_jobs', ['status' => ['completed', 'ready_for_billing', 'qc_passed']]),
            'unposted_invoices' => $this->safeCount('auto_service_invoices', ['posting_status' => ['pending', 'draft', null]]),
            'unlinked_parts' => $this->safeCount('auto_service_job_parts', ['product_id' => [0, null]]),
            'pending_notifications' => $this->safeCount('auto_service_notification_logs', ['status' => ['pending', 'queued', 'failed']]),
        ];

        $recentBridges = collect();
        try {
            if ($schema->hasTable('auto_service_integration_bridge_logs')) {
                $recentBridges = $this->tenantDb()->table('auto_service_integration_bridge_logs')
                    ->when($businessId, fn($q) => $q->where('business_id', $businessId))
                    ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                    ->orderByDesc('id')->limit(25)->get();
            }
        } catch (\Throwable $e) { $recentBridges = collect(); }

        return view('autoservice::enterprise_integration.index', compact('integrationChecks', 'postingSummary', 'recentBridges'));
    }

    public function logCheck(Request $request)
    {
        $schema = Schema::connection($this->autoServiceTenantConnection());
        if (!$schema->hasTable('auto_service_integration_bridge_logs')) {
            return redirect()->back()->with('status', 'Please run Stage 043 SQL first.');
        }
        $this->tenantDb()->table('auto_service_integration_bridge_logs')->insert([
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'bridge_type' => $request->input('bridge_type', 'manual_check'),
            'source_module' => 'AutoService',
            'target_module' => $request->input('target_module', 'ERP'),
            'reference_type' => 'readiness_check',
            'reference_id' => 0,
            'status' => 'checked',
            'message' => 'Manual integration readiness check completed from Stage 043 Enterprise Integration Centre.',
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('status', 'Integration check logged successfully.');
    }

    protected function safeCount(string $table, array $filters = []): int
    {
        try {
            $schema = Schema::connection($this->autoServiceTenantConnection());
            if (!$schema->hasTable($table)) { return 0; }
            $query = $this->tenantDb()->table($table);
            if ($this->businessId() && $schema->hasColumn($table, 'business_id')) { $query->where('business_id', $this->businessId()); }
            if ($this->locationId() && $schema->hasColumn($table, 'location_id')) { $query->where('location_id', $this->locationId()); }
            foreach ($filters as $column => $values) {
                if (!$schema->hasColumn($table, $column)) { continue; }
                $values = is_array($values) ? $values : [$values];
                $query->where(function ($q) use ($column, $values) {
                    foreach ($values as $value) {
                        is_null($value) ? $q->orWhereNull($column) : $q->orWhere($column, $value);
                    }
                });
            }
            return (int) $query->count();
        } catch (\Throwable $e) { return 0; }
    }
}
