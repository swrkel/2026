<?php

namespace Modules\StockTransferNew\Services\TesterSupport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestCaseService
{
    public function groupedTestCases(): array
    {
        return [
            'Foundation' => [
                ['key' => 'foundation_sidebar', 'title' => 'Sidebar menu is visible for permitted users'],
                ['key' => 'foundation_dashboard', 'title' => 'Command Center loads without DataTables processing lock'],
                ['key' => 'foundation_product_bridge', 'title' => 'Product lookup works from existing Products module'],
            ],
            'Request' => [
                ['key' => 'request_create', 'title' => 'Create transfer request with business/location/store scope'],
                ['key' => 'request_draft_edit', 'title' => 'Draft can be edited before approval'],
                ['key' => 'request_stock_check', 'title' => 'Insufficient stock rule is applied only when enabled'],
            ],
            'Approval' => [
                ['key' => 'approval_matrix', 'title' => 'Approval matrix chooses correct approvers'],
                ['key' => 'approval_return', 'title' => 'Return for correction keeps remarks/history'],
                ['key' => 'approval_lock', 'title' => 'Approved transfers are locked from unsafe edits'],
            ],
            'Dispatch / Receive' => [
                ['key' => 'dispatch_stock_ledger', 'title' => 'Dispatch creates outgoing stock movement'],
                ['key' => 'receive_stock_ledger', 'title' => 'Receive creates incoming stock movement'],
                ['key' => 'variance_reconcile', 'title' => 'Short/excess variance can be reconciled'],
            ],
            'Reports' => [
                ['key' => 'report_product', 'title' => 'Product-wise report matches transfer lines'],
                ['key' => 'report_location_store', 'title' => 'Location/store report respects tenant/business scope'],
                ['key' => 'report_export', 'title' => 'CSV export opens with correct columns'],
            ],
        ];
    }

    public function saveStatus(array $data): void
    {
        if (! Schema::hasTable('stn_tester_case_statuses')) {
            return;
        }

        DB::table('stn_tester_case_statuses')->updateOrInsert(
            ['test_key' => $data['test_key']],
            [
                'status' => $data['status'],
                'remarks' => $data['remarks'] ?? null,
                'tested_by' => auth()->id(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
