<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Entities\PdnewPrintLog;

class PrintController extends PdnewController
{
    public function settlement(Request $request, int $settlement)
    {
        $model = $this->settlement($settlement)->load([
            'sourceImport', 'pumps', 'meterSales', 'payments.details', 'creditSales.lines',
            'otherSales.lines', 'unloadStocks.lines', 'dayEntries', 'collections',
            'recoveries', 'commissions', 'adjustments', 'issues', 'history',
        ]);
        $this->log('settlement', $model->id, (string) $request->input('print_type', 'original'));

        return view('petropdnew::print.settlement', ['settlement' => $model]);
    }

    public function dayEnd(Request $request, int $dayEnd)
    {
        $model = $this->dayEnd($dayEnd)->load('settlements');
        $this->log('day_end', $model->id, (string) $request->input('print_type', 'original'));

        return view('petropdnew::print.day-end', ['dayEnd' => $model]);
    }

    private function log(string $type, int $id, string $printType): void
    {
        PdnewPrintLog::query()->create([
            'business_id' => $this->context->businessId(),
            'document_type' => $type,
            'document_id' => $id,
            'print_type' => in_array($printType, ['original', 'reprint', 'preview'], true) ? $printType : 'reprint',
            'printed_by' => $this->context->userId(),
            'printed_at' => now(),
            'ip_address' => request()->ip(),
            'metadata' => [],
        ]);
    }
}
