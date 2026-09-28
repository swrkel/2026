<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ReportsOther\Models\Receipt;
use Modules\ReportsOther\Models\Source;
use Modules\ReportsOther\Services\AmountWordsService;
use Modules\ReportsOther\Services\BusinessSettingsGateway;
use Modules\ReportsOther\Services\ReceiptSourceDataGateway;
use Modules\ReportsOther\Services\ReportFormatter;
use Modules\ReportsOther\Services\SequenceService;
use Modules\ReportsOther\Support\CurrentScope;

class ReceiptPreviewController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentScope $scope,
        ReceiptSourceDataGateway $gateway,
        SequenceService $sequence,
        BusinessSettingsGateway $settings,
        AmountWordsService $amountWords,
        ReportFormatter $formatter,
    ) {
        $data = $request->validate([
            'source_id' => ['required', 'integer', 'min:1'],
            'receipt_date' => ['required', 'date'],
        ]);

        $source = Source::query()
            ->where('scope_key', $scope->key())
            ->whereKey($data['source_id'])
            ->firstOrFail();

        $existing = Receipt::query()
            ->where('scope_key', $scope->key())
            ->whereDate('receipt_date', $data['receipt_date'])
            ->where('source_id', $source->id)
            ->first();

        $snapshot = $gateway->snapshot($source, $data['receipt_date']);
        $precision = $settings->currencyPrecision($scope->businessId());
        $total = (float) ($snapshot['total'] ?? 0);

        return response()->json([
            'available' => (bool) ($snapshot['available'] ?? false),
            'message' => $snapshot['message'] ?? null,
            'source_name' => $source->source_name,
            'receipt_no' => $existing?->receipt_no ?? $sequence->peek(config('reportsother.receipt_document_key')),
            'numbering_configured' => (bool) $sequence->current(config('reportsother.receipt_document_key')),
            'existing' => $existing ? [
                'id' => $existing->id,
                'receipt_no' => $existing->receipt_no,
                'view_url' => route('reports-other.cash-receipt.receipts.show', $existing),
            ] : null,
            'membership_no' => $snapshot['membership_no'] ?? null,
            'membership_is_manual' => empty($snapshot['membership_no']),
            'details' => array_map(fn ($detail) => $detail + [
                'amount_formatted' => $formatter->amount($detail['amount'], $scope->businessId()),
            ], $snapshot['details'] ?? []),
            'total' => $total,
            'total_formatted' => $formatter->amount($total, $scope->businessId()),
            'amount_in_words' => $amountWords->convert($total, $precision),
            'cheques' => $snapshot['cheques'] ?? [],
            'matched_transactions' => (int) ($snapshot['matched_transactions'] ?? 0),
        ]);
    }
}
