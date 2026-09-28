@include('managementreport::daily.partials.report-header', ['report' => $report])

@php
    /*
     * Total Add + Total Out are a single visual report block.
     * Keep both services/selection keys independent, but when both are present
     * render them together so the two totals stay aligned on screen and print.
     */
    $reportSections = array_values(data_get($report, 'sections', []));
    $sectionNumbers = [];
    $totalAddSection = null;
    $totalOutSection = null;
    $totalPairFirstIndex = null;
    $financialStatusSection = null;
    $financialStatusTwoSection = null;
    $financialPairRendered = false;
    $outstandingSection = null;
    $stockValueSection = null;
    $outstandingStockPairRendered = false;

    foreach ($reportSections as $sectionIndex => $candidateSection) {
        $candidateKey = data_get($candidateSection, 'key');
        $sectionNumbers[$candidateKey] = str_pad($sectionIndex + 1, 2, '0', STR_PAD_LEFT);

        if ($candidateKey === 'total_add') {
            $totalAddSection = $candidateSection;
            $totalPairFirstIndex = is_null($totalPairFirstIndex)
                ? $sectionIndex
                : min($totalPairFirstIndex, $sectionIndex);
        }

        if ($candidateKey === 'financial_status') {
            $financialStatusSection = $candidateSection;
        }

        if ($candidateKey === 'financial_status_two') {
            $financialStatusTwoSection = $candidateSection;
        }

        if ($candidateKey === 'out') {
            $totalOutSection = $candidateSection;
            $totalPairFirstIndex = is_null($totalPairFirstIndex)
                ? $sectionIndex
                : min($totalPairFirstIndex, $sectionIndex);
        }

        if ($candidateKey === 'outstanding') {
            $outstandingSection = $candidateSection;
        }

        if ($candidateKey === 'stock_value') {
            $stockValueSection = $candidateSection;
        }
    }

    $hasTotalPair = !empty($totalAddSection) && !empty($totalOutSection);
    $hasFinancialPair = !empty($financialStatusSection) && !empty($financialStatusTwoSection);
    $hasOutstandingStockPair = !empty($outstandingSection) && !empty($stockValueSection);
    $totalPairRendered = false;

    // The visual order is always Total Add on the left and Total Out on the right,
    // even for old saved snapshots that stored the two sections in the reverse order.
    $totalAddDisplayNumber = !is_null($totalPairFirstIndex)
        ? str_pad($totalPairFirstIndex + 1, 2, '0', STR_PAD_LEFT)
        : '';
    $totalOutDisplayNumber = !is_null($totalPairFirstIndex)
        ? str_pad($totalPairFirstIndex + 2, 2, '0', STR_PAD_LEFT)
        : '';
@endphp

@foreach($reportSections as $section)
    @php
        $sectionKey = data_get($section, 'key');
    @endphp

    @if($hasTotalPair && in_array($sectionKey, ['total_add', 'out'], true))
        @if(!$totalPairRendered)
            <section class="mgmt-report-section mgmt-total-add-out-section" data-section="total_add,out">
                @include('managementreport::daily.sections.total-add-out', [
                    'totalAddSection' => $totalAddSection,
                    'totalOutSection' => $totalOutSection,
                    'totalAddNumber' => $totalAddDisplayNumber,
                    'totalOutNumber' => $totalOutDisplayNumber,
                    'meta' => data_get($report, 'meta', []),
                ])
            </section>
            @php
                $totalPairRendered = true;
            @endphp
        @endif
    @elseif($hasFinancialPair && in_array($sectionKey, ['financial_status', 'financial_status_two'], true))
        @if(!$financialPairRendered)
            <section class="mgmt-report-section mgmt-financial-pair-section" data-section="financial_status,financial_status_two">
                @include('managementreport::daily.sections.financial-status-pair', [
                    'financialStatusSection' => $financialStatusSection,
                    'financialStatusTwoSection' => $financialStatusTwoSection,
                    'financialStatusNumber' => data_get($sectionNumbers, 'financial_status', ''),
                    'financialStatusTwoNumber' => data_get($sectionNumbers, 'financial_status_two', ''),
                    'meta' => data_get($report, 'meta', []),
                ])
            </section>
            @php
                $financialPairRendered = true;
            @endphp
        @endif
    @elseif($hasOutstandingStockPair && in_array($sectionKey, ['outstanding', 'stock_value'], true))
        @if(!$outstandingStockPairRendered)
            <section class="mgmt-report-section mgmt-outstanding-stock-section" data-section="outstanding,stock_value">
                @include('managementreport::daily.sections.outstanding-stock-pair', [
                    'outstandingSection' => $outstandingSection,
                    'stockValueSection' => $stockValueSection,
                    'outstandingNumber' => data_get($sectionNumbers, 'outstanding', ''),
                    'stockValueNumber' => data_get($sectionNumbers, 'stock_value', ''),
                    'meta' => data_get($report, 'meta', []),
                ])
            </section>
            @php
                $outstandingStockPairRendered = true;
            @endphp
        @endif
    @else
        <section class="mgmt-report-section" data-section="{{ $sectionKey }}">
            <div class="mgmt-report-section-title">
                <span>{{ data_get($sectionNumbers, $sectionKey, '') }}</span>
                <h3>{{ $section['label'] }}</h3>
            </div>
            @include($section['view'], ['data' => $section['payload'], 'meta' => data_get($report, 'meta', [])])
        </section>
    @endif
@endforeach
