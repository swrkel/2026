@php
    $reportTableId = 'rcm-report-table';
    $exportBaseName = 'rice-mill-' . str_replace('_', '-', $activeTab ?? 'report');
@endphp
<div class="rcm-card rcm-report-panel" data-rcm-loaded-tab="{{ $activeTab ?? '' }}">
    <div class="rcm-panel-head"><h3>{{ $title }}</h3><span class="rcm-panel-hint">Rice Mill Reports</span></div>

    @include('RiceMill::partials.functionality-bar', ['tableId'=>$reportTableId,'exportName'=>$exportBaseName,'serverPaged'=>false,'rowsLabel'=>'records','dateEnabled'=>true])

    <form class="rcm-toolbar rcm-report-filter rcm-report-location-filter" method="get" action="{{ route('rice-mill.reports.index') }}">
        <input type="hidden" name="tab" value="{{ $activeTab ?? 'purchases' }}">
        <input type="hidden" name="range" value="{{ request('range','this_year') }}">
        <input type="hidden" name="q" value="{{ request('q') }}">
        <input type="hidden" name="per_page" value="{{ request('per_page','25') }}">
        @if(request('range')==='custom')<input type="hidden" name="from" value="{{ request('from') }}"><input type="hidden" name="to" value="{{ request('to') }}">@endif
        @include('RiceMill::reports.partials.location-store-filter')
        <button class="rcm-btn" type="submit"><i class="fa fa-filter"></i> Apply Location / Store</button>
    </form>

    <div class="rcm-table-wrap">
        <table id="{{ $reportTableId }}" class="rcm-table rcm-managed-table">
            <thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        @foreach($columns as $key => $label)
                            @php
                                $value = data_get($r, $key);
                                $quantityColumn = str_contains($key, 'qty') || str_contains($key, 'weight');
                                $currencyColumn = str_contains($key, 'total') || str_contains($key, 'cost') || str_contains($key, 'amount') || str_contains($key, 'rate') || str_contains($key, 'price') || str_contains($key, 'sales') || str_contains($key, 'margin');
                                $percentColumn = str_contains($key, 'percent');
                                $numericColumn = $quantityColumn || $currencyColumn || $percentColumn;
                                if (is_numeric($value)) {
                                    if ($quantityColumn) $displayValue = number_format((float)$value,$rcmQuantityPrecision);
                                    elseif ($currencyColumn) $displayValue = number_format((float)$value,$rcmCurrencyPrecision);
                                    elseif ($percentColumn) $displayValue = number_format((float)$value,2);
                                    else $displayValue = $value;
                                } else $displayValue = $value;
                            @endphp
                            <td @if($numericColumn) class="rcm-num" @endif>{{ $displayValue }}</td>
                        @endforeach
                    </tr>
                @empty<tr data-rcm-empty-row><td colspan="{{ count($columns) }}" class="rcm-muted" style="text-align:center;padding:24px;">No records found for this report.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
