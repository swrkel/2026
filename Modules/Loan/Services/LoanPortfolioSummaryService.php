<?php

namespace Modules\Loan\Services;

use Illuminate\Support\Facades\DB;

class LoanPortfolioSummaryService
{
    public function summary(?int $businessId = null, ?int $locationId = null): array
    {
        $loanTable = 'loans';
        $summary = [
            'total_loans' => 0,
            'active_loans' => 0,
            'closed_loans' => 0,
            'overdue_loans' => 0,
            'principal_outstanding' => 0.0,
        ];

        if (! DB::getSchemaBuilder()->hasTable($loanTable)) {
            return $summary;
        }

        $query = DB::table($loanTable);

        if ($businessId && DB::getSchemaBuilder()->hasColumn($loanTable, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        if ($locationId && DB::getSchemaBuilder()->hasColumn($loanTable, 'location_id')) {
            $query->where('location_id', $locationId);
        }

        $summary['total_loans'] = (clone $query)->count();

        if (DB::getSchemaBuilder()->hasColumn($loanTable, 'status')) {
            $summary['active_loans'] = (clone $query)->whereIn('status', ['active', 'disbursed', 'approved'])->count();
            $summary['closed_loans'] = (clone $query)->whereIn('status', ['closed', 'settled', 'written_off'])->count();
            $summary['overdue_loans'] = (clone $query)->whereIn('status', ['overdue', 'arrears'])->count();
        }

        foreach (['principal_outstanding', 'outstanding_principal', 'balance'] as $column) {
            if (DB::getSchemaBuilder()->hasColumn($loanTable, $column)) {
                $summary['principal_outstanding'] = (float) (clone $query)->sum($column);
                break;
            }
        }

        return $summary;
    }
}
