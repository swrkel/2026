<?php
namespace Modules\AirlineTicketingNew\Services\Certification;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CertificationService
{
    public function run(int $businessId): array
    {
        $checks = [
            'tables' => $this->tableCheck(),
            'duplicate_document_numbers' => $this->duplicateDocumentNumbers($businessId),
            'unbalanced_journals' => $this->unbalancedJournals($businessId),
            'negative_invoice_due' => $this->negativeInvoiceDue($businessId),
            'orphan_ticket_segments' => $this->orphanTicketSegments($businessId),
        ];

        return [
            'status' => collect($checks)->every(fn ($value) => $value === true || $value === 0)
                ? 'passed'
                : 'attention_required',
            'checks' => $checks,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    private function tableCheck(): bool
    {
        return collect([
            'atn_reservations','atn_tickets','atn_invoices','atn_payments',
            'atn_refunds','atn_journal_entries','atn_workflow_instances',
        ])->every(fn ($table) => Schema::hasTable($table));
    }

    private function duplicateDocumentNumbers(int $businessId): int
    {
        return DB::table('atn_tickets')
            ->where('business_id', $businessId)
            ->select('ticket_no')
            ->groupBy('ticket_no')
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }

    private function unbalancedJournals(int $businessId): int
    {
        return DB::table('atn_journal_entries')
            ->where('business_id', $businessId)
            ->whereRaw('ABS(total_debit - total_credit) > 0.0001')
            ->count();
    }

    private function negativeInvoiceDue(int $businessId): int
    {
        return DB::table('atn_invoices')
            ->where('business_id', $businessId)
            ->where('due_total', '<', 0)
            ->count();
    }

    private function orphanTicketSegments(int $businessId): int
    {
        return DB::table('atn_ticket_segments as s')
            ->leftJoin('atn_tickets as t', 't.id', '=', 's.ticket_id')
            ->where('s.business_id', $businessId)
            ->whereNull('t.id')
            ->count();
    }
}
