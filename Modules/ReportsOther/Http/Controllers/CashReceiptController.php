<?php

namespace Modules\ReportsOther\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Modules\ReportsOther\Models\Receipt;
use Modules\ReportsOther\Models\Source;
use Modules\ReportsOther\Services\BusinessSettingsGateway;
use Modules\ReportsOther\Services\OrganizationGateway;
use Modules\ReportsOther\Services\ProductCatalogGateway;
use Modules\ReportsOther\Services\SequenceService;
use Modules\ReportsOther\Support\CurrentScope;

class CashReceiptController extends Controller
{
    public function index(
        CurrentScope $scope,
        ProductCatalogGateway $catalog,
        SequenceService $sequence,
        OrganizationGateway $organisation,
        BusinessSettingsGateway $settings,
    ) {
        $activeTab = request('tab', 'receipt');
        if (!in_array($activeTab, ['receipt', 'list', 'mapping', 'numbering'], true)) {
            $activeTab = 'receipt';
        }

        // REO-PERF-001: Build only the data needed by the requested tab.
        // This keeps AJAX tab fragments small and avoids unnecessary DB/schema work.
        $data = [
            'activeTab' => $activeTab,
            'sources' => collect(),
            'catalogTree' => collect(),
            'numbering' => null,
            'nextReceiptNo' => null,
            'organisation' => [
                'business_name' => 'Business',
                'location_name' => 'Location',
                'location_address' => '',
            ],
            'receipts' => collect(),
            'dateFrom' => null,
            'dateTo' => null,
            'currencyPrecision' => 2,
            'financialYearStartMonth' => 1,
        ];

        if ($activeTab === 'receipt') {
            $data['sources'] = Source::query()
                ->select(['id', 'source_name', 'scope_key'])
                ->where('scope_key', $scope->key())
                ->orderBy('source_name')
                ->get();

            // One sequence query only. v4 called current() and peek() separately.
            $data['numbering'] = $sequence->current(config('reportsother.receipt_document_key'));
            if ($data['numbering']) {
                $data['nextReceiptNo'] = (string) ($data['numbering']->prefix ?? '').(int) $data['numbering']->next_number;
            }

            $data['organisation'] = $organisation->identity();
            $data['currencyPrecision'] = $settings->currencyPrecision($scope->businessId());
        }

        if ($activeTab === 'mapping') {
            $data['sources'] = Source::query()
                ->select(['id', 'source_name', 'created_at', 'created_by_name', 'scope_key'])
                ->with(['mappings' => function ($query) {
                    $query->select([
                        'id', 'source_id', 'item_type', 'item_id', 'item_name_snapshot', 'sort_order',
                    ])->orderBy('item_type')->orderBy('item_name_snapshot');
                }])
                ->where('scope_key', $scope->key())
                ->orderBy('source_name')
                ->get();

            $data['catalogTree'] = $catalog->tree($scope->businessId());
        }

        if ($activeTab === 'numbering') {
            $data['numbering'] = $sequence->current(config('reportsother.receipt_document_key'));
        }

        if ($activeTab === 'list') {
            [$from, $to] = $this->dateRange();
            $data['dateFrom'] = $from->toDateString();
            $data['dateTo'] = $to->toDateString();
            $data['currencyPrecision'] = $settings->currencyPrecision($scope->businessId());
            $data['financialYearStartMonth'] = $settings->financialYearStartMonth($scope->businessId());

            $data['receipts'] = Receipt::query()
                ->select([
                    'id', 'scope_key', 'receipt_date', 'receipt_no', 'source_name',
                    'total_amount', 'entered_by_name',
                ])
                ->with(['audits' => function ($query) {
                    $query->select([
                        'id', 'receipt_id', 'field_label', 'old_value', 'new_value',
                        'edited_by_name', 'edited_at',
                    ])->orderByDesc('edited_at')->orderByDesc('id');
                }])
                ->where('scope_key', $scope->key())
                ->whereBetween('receipt_date', [$from->toDateString(), $to->toDateString()])
                ->orderByDesc('receipt_date')
                ->orderByDesc('id')
                ->get();
        }

        // Fragment responses are used by the instant tab engine. They do not
        // re-render the page shell, CSS or JavaScript.
        if (request()->boolean('fragment')) {
            return view($this->partialView($activeTab), $data);
        }

        return view('reportsother::cash-receipt.index', $data);
    }

    private function partialView(string $tab): string
    {
        return match ($tab) {
            'list' => 'reportsother::cash-receipt.partials.list',
            'mapping' => 'reportsother::cash-receipt.partials.mapping',
            'numbering' => 'reportsother::cash-receipt.partials.numbering',
            default => 'reportsother::cash-receipt.partials.receipt',
        };
    }

    private function dateRange(): array
    {
        try {
            $from = request('date_from') ? Carbon::parse(request('date_from'))->startOfDay() : now()->startOfMonth();
        } catch (\Throwable) {
            $from = now()->startOfMonth();
        }
        try {
            $to = request('date_to') ? Carbon::parse(request('date_to'))->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            $to = now()->startOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        return [$from, $to];
    }
}
