<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;

class AutoServiceAccountingAdapter
{
    public function businessId(): ?int
    {
        return session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id;
    }

    public function enabled(): bool
    {
        $businessId = $this->businessId();
        if (!$businessId) {
            return false;
        }
        return (string) DB::table('auto_service_settings')
            ->where('business_id', $businessId)
            ->where('key', 'enable_accounting_posting')
            ->value('value') === '1';
    }

    public function accounts(): array
    {
        $businessId = $this->businessId();
        $keys = [
            'account_labour_income',
            'account_parts_income',
            'account_tax_payable',
            'account_cash_bank',
            'account_customer_receivable',
            'account_parts_cost',
            'account_inventory',
        ];
        $rows = DB::table('auto_service_settings')
            ->where('business_id', $businessId)
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();
        return array_merge(array_fill_keys($keys, null), $rows);
    }

    public function invoiceSummary(int $invoiceId): array
    {
        $invoice = DB::table('auto_service_invoices')->where('id', $invoiceId)->first();
        $lines = DB::table('auto_service_invoice_lines')->where('invoice_id', $invoiceId)->get();
        return [
            'invoice' => $invoice,
            'lines' => $lines,
            'labour_total' => (float) $lines->where('line_type', 'labour')->sum('line_total'),
            'parts_total' => (float) $lines->where('line_type', 'part')->sum('line_total'),
            'tax_total' => (float) ($invoice->tax_total ?? 0),
            'grand_total' => (float) ($invoice->grand_total ?? 0),
            'paid_total' => (float) ($invoice->paid_total ?? 0),
            'balance_due' => (float) ($invoice->balance_due ?? 0),
        ];
    }

    public function paymentSummary(int $paymentId): array
    {
        $payment = DB::table('auto_service_payments')->where('id', $paymentId)->first();
        return [
            'payment' => $payment,
            'amount' => (float) ($payment->amount ?? 0),
            'method' => $payment->payment_method ?? null,
        ];
    }

    public function buildInvoicePostingPreview(int $invoiceId): array
    {
        $summary = $this->invoiceSummary($invoiceId);
        $accounts = $this->accounts();
        $rows = [];
        if ($summary['grand_total'] > 0) {
            $rows[] = ['account' => $accounts['account_customer_receivable'], 'type' => 'debit', 'amount' => $summary['grand_total'], 'description' => 'Auto Service invoice receivable'];
        }
        if ($summary['labour_total'] > 0) {
            $rows[] = ['account' => $accounts['account_labour_income'], 'type' => 'credit', 'amount' => $summary['labour_total'], 'description' => 'Auto Service labour income'];
        }
        if ($summary['parts_total'] > 0) {
            $rows[] = ['account' => $accounts['account_parts_income'], 'type' => 'credit', 'amount' => $summary['parts_total'], 'description' => 'Auto Service parts income'];
        }
        if ($summary['tax_total'] > 0) {
            $rows[] = ['account' => $accounts['account_tax_payable'], 'type' => 'credit', 'amount' => $summary['tax_total'], 'description' => 'Auto Service tax payable'];
        }
        return $rows;
    }

    public function markInvoicePosted(int $invoiceId, ?string $reference = null): void
    {
        DB::table('auto_service_invoices')->where('id', $invoiceId)->update([
            'accounting_status' => 'posted',
            'accounting_reference' => $reference ?: 'AS-INV-' . $invoiceId,
            'accounting_posted_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
