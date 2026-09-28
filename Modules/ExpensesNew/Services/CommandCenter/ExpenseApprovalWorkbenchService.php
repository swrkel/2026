<?php

namespace Modules\ExpensesNew\Services\CommandCenter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseApprovalWorkbenchService
{
    public function payload(Request $request): array
    {
        return [
            'title' => 'Approval Workbench',
            'queues' => DB::table('expnew_approval_queues')->orderBy('priority', 'desc')->limit(100)->get(),
        ];
    }

    public function execute(Request $request): array
    {
        $ids = $request->get('ids', []);
        $action = $request->get('action');
        DB::table('expnew_approval_queues')->whereIn('id', $ids)->update([
            'last_action' => $action,
            'last_action_by' => auth()->id(),
            'last_action_at' => now(),
            'last_comments' => $request->get('comments'),
            'updated_at' => now(),
        ]);
        return ['success' => true, 'message' => 'Approval action recorded successfully.'];
    }
}
