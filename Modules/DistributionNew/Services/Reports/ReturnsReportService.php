<?php

namespace Modules\DistributionNew\Services\Reports;

use Modules\DistributionNew\Models\DisnewReturn;
use Modules\DistributionNew\Models\DisnewCreditNote;

class ReturnsReportService
{
    public function summary(array $filters = []): array
    {
        $returns = DisnewReturn::query()->where('business_id', $filters['business_id']);
        $creditNotes = DisnewCreditNote::query()->where('business_id', $filters['business_id']);
        if (!empty($filters['start_date'])) {
            $returns->whereDate('return_date', '>=', $filters['start_date']);
            $creditNotes->whereDate('credit_note_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $returns->whereDate('return_date', '<=', $filters['end_date']);
            $creditNotes->whereDate('credit_note_date', '<=', $filters['end_date']);
        }
        return [
            'return_count' => (clone $returns)->count(),
            'return_total' => (clone $returns)->sum('total_amount'),
            'credit_note_count' => (clone $creditNotes)->count(),
            'credit_note_total' => (clone $creditNotes)->sum('total_amount'),
        ];
    }
}
