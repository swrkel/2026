<?php

namespace Modules\ExpensesNew\Services\CommandCenter;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ExpensesNew\Utils\BusinessScope;

class ExpenseCommandCenterService
{
    public function summary(Request $request): array
    {
        return [
            'title' => 'Expenses-New Command Center',
            'cards' => $this->widgets($request),
            'filters' => [
                'business_id' => $request->get('business_id'),
                'location_id' => $request->get('location_id'),
                'date_range' => $request->get('date_range'),
            ],
        ];
    }

    public function widgets(Request $request): array
    {
        $businessId = $request->get('business_id') ?: BusinessScope::businessId();
        $locationId = $request->get('location_id');

        $base = DB::table('expnew_expenses')
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->when($locationId, fn($q) => $q->where('location_id', $locationId));

        return [
            ['key' => 'today', 'label' => 'Today Expenses', 'value' => (clone $base)->whereDate('expense_date', now()->toDateString())->sum('total_amount')],
            ['key' => 'month', 'label' => 'This Month', 'value' => (clone $base)->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount')],
            ['key' => 'pending_approval', 'label' => 'Pending Approval', 'value' => (clone $base)->where('status', 'submitted')->count()],
            ['key' => 'pending_payment', 'label' => 'Pending Payment', 'value' => (clone $base)->whereIn('status', ['approved','payment_pending'])->sum('due_amount')],
            ['key' => 'over_budget', 'label' => 'Over Budget Items', 'value' => DB::table('expnew_command_alerts')->where('alert_type', 'budget_overrun')->where('is_resolved', 0)->count()],
            ['key' => 'documents', 'label' => 'Docs Awaiting Review', 'value' => DB::table('expnew_command_alerts')->where('alert_type', 'missing_document')->where('is_resolved', 0)->count()],
        ];
    }
}
