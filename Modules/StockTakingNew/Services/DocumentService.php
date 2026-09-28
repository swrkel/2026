<?php

namespace Modules\StockTakingNew\Services;

use Modules\StockTakingNew\Entities\StockTakeSession;

class DocumentService
{
    public function viewData(StockTakeSession $session, string $type): array
    {
        $lines = $session->lines()->orderBy('product_name')->orderBy('sku')->get();
        if ($type === 'variance') {
            $lines = $lines->filter(fn ($line) => abs((float) $line->variance_qty) > 0.0000001)->values();
        }

        $isCountSheet = $type === 'count_sheet';

        return [
            'session' => $session,
            'lines' => $lines,
            'documentType' => $type,
            'locationName' => app(MasterDataBridgeService::class)->locationName($session->location_id),
            'storeName' => app(MasterDataBridgeService::class)->storeName($session->store_id),
            'isCountSheet' => $isCountSheet,
            'showSystemQuantity' => ! $isCountSheet || $session->count_mode === 'open',
            'showFinancials' => ! $isCountSheet,
        ];
    }

    public function pdf(StockTakeSession $session, string $type, bool $download = false)
    {
        $html = view('stocktakingnew::documents.print', $this->viewData($session, $type))->render();
        if (! app()->bound('dompdf.wrapper')) {
            return response($html);
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('a4', 'landscape');
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $session->stock_take_no . '-' . $type) . '.pdf';

        return $download ? $pdf->download($name) : $pdf->stream($name);
    }
}
