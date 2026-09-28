<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FreightSettlementService
{
    public function datatable(Request $request): array
    {
        $businessId = (int) session('user.business_id');
        $rows = DB::table('stn_freight_invoices as fi')
            ->leftJoin('stn_carriers as c', 'c.id', '=', 'fi.carrier_id')
            ->where('fi.business_id', $businessId)
            ->select('fi.id','fi.invoice_no','c.name as carrier_name','fi.invoice_date','fi.amount','fi.approved_amount','fi.status')
            ->orderByDesc('fi.id')
            ->limit(500)
            ->get();

        return ['data' => $rows];
    }

    public function reconcile(int $invoiceId, array $payload): void
    {
        $businessId = (int) session('user.business_id');
        DB::transaction(function () use ($invoiceId, $businessId, $payload) {
            $invoice = DB::table('stn_freight_invoices')
                ->where('business_id', $businessId)
                ->where('id', $invoiceId)
                ->lockForUpdate()
                ->first();

            abort_if(!$invoice, 404, 'Freight invoice not found');

            DB::table('stn_freight_reconciliations')->insert([
                'business_id' => $businessId,
                'freight_invoice_id' => $invoiceId,
                'system_amount' => $invoice->amount,
                'approved_amount' => $payload['approved_amount'] ?? $invoice->approved_amount,
                'difference_amount' => ($payload['approved_amount'] ?? $invoice->approved_amount) - $invoice->amount,
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('stn_freight_invoices')->where('id', $invoiceId)->update([
                'status' => 'reconciled',
                'updated_at' => now(),
            ]);
        });
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $businessId = (int) session('user.business_id');
        return response()->streamDownload(function () use ($businessId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice No','Carrier','Invoice Date','Amount','Approved Amount','Status']);
            DB::table('stn_freight_invoices as fi')
                ->leftJoin('stn_carriers as c', 'c.id', '=', 'fi.carrier_id')
                ->where('fi.business_id', $businessId)
                ->orderByDesc('fi.id')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [$row->invoice_no, $row->name, $row->invoice_date, $row->amount, $row->approved_amount, $row->status]);
                    }
                });
            fclose($out);
        }, 'stn_freight_settlement.csv');
    }
}
