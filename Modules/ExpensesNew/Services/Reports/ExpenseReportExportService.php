<?php

namespace Modules\ExpensesNew\Services\Reports;

class ExpenseReportExportService
{
    public function export(string $code, string $format, array $filters = [])
    {
        $filename = 'expenses_new_' . $code . '_' . now()->format('Ymd_His') . '.csv';
        return response("Report,Status
{$code},Export placeholder
", 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
