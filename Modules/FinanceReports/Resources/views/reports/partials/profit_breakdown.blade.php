{{--
    IS2059: the nine Profit & Loss breakdowns, tab-wise.

    Each tab is a link that reloads the report with ?profit_tab=..., keeping the
    date range and location already chosen. Server-side rather than client-side
    tabs on purpose: only the selected breakdown is queried, so opening the
    report runs ONE grouped query rather than nine.

    The table itself is deliberately plain so the module's existing toolbar -
    Export to CSV / Excel / PDF / Print and Column Visibility - keeps working
    against it without special handling.
--}}
<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title">Profit Breakdown</h3>
    </div>

    <div class="box-body">
        <ul class="nav nav-tabs fr-profit-tabs no-print" role="tablist">
            @foreach($profit_tabs as $key => $tab)
                <li role="presentation" class="{{ $profit_tab === $key ? 'active' : '' }}">
                    <a href="{{ route('finance-reports.profit-loss-new', array_merge(request()->except('page'), ['profit_tab' => $key])) }}">
                        {{ $tab['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        {{--
            IS2059: split by location.

            Only offered where it means something - not on the locations tab,
            which already groups by location, and not when a single location is
            already filtered, where every row would repeat the same value.
        --}}
        @if($profit_tab !== 'locations' && empty($location_id))
            <div class="fr-profit-split no-print">
                <a href="{{ route('finance-reports.profit-loss-new', array_merge(request()->except('page'), ['split_location' => $split_location ? 0 : 1])) }}"
                   class="btn btn-xs {{ $split_location ? 'btn-primary' : 'btn-default' }}">
                    <i class="fa {{ $split_location ? 'fa-check-square-o' : 'fa-square-o' }}"></i>
                    Split by location
                </a>
                <span class="fr-profit-split-hint">
                    Shows each {{ strtolower($profit_tabs[$profit_tab]['column'] ?? 'row') }} separately per location,
                    so a line that is profitable at one site and loss-making at another is visible.
                </span>
            </div>
        @endif

        <div class="fr-profit-print-title">
            {{ $profit_tabs[$profit_tab]['label'] ?? 'Profit Breakdown' }}
        </div>

        <div class="table-responsive fr-profit-table-wrap">
            <table class="table table-bordered table-striped fr-profit-table" id="fr_profit_breakdown_table">
                <thead>
                    <tr>
                        <th>{{ $profit_tabs[$profit_tab]['column'] ?? 'Description' }}</th>
                        @if(! empty($show_location_column))
                            <th>Location</th>
                        @endif
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Cost</th>
                        <th class="text-right">Discount</th>
                        <th class="text-right">Gross Profit</th>
                        <th class="text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profit_rows as $row)
                        <tr>
                            <td>{{ $row->label }}</td>
                            @if(! empty($show_location_column))
                                <td>{{ $row->location_label ?? '—' }}</td>
                            @endif
                            <td class="text-right">{{ number_format($row->quantity, 2) }}</td>
                            <td class="text-right">{{ number_format($row->revenue, 2) }}</td>
                            <td class="text-right">{{ number_format($row->cost, 2) }}</td>
                            <td class="text-right">{{ number_format($row->discount ?? 0, 2) }}</td>
                            {{-- A loss is shown in red, so it cannot be mistaken for a
                                 small profit when scanning a long list. --}}
                            <td class="text-right {{ $row->profit < 0 ? 'fr-profit-negative' : '' }}">
                                {{ number_format($row->profit, 2) }}
                            </td>
                            <td class="text-right {{ $row->margin < 0 ? 'fr-profit-negative' : '' }}">
                                {{ number_format($row->margin, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ! empty($show_location_column) ? 8 : 7 }}" class="text-center">
                                No sales found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($profit_rows) > 0)
                    <tfoot>
                        <tr class="fr-profit-total-row">
                            <th>Total</th>
                            @if(! empty($show_location_column))
                                <th></th>
                            @endif
                            <th class="text-right">{{ number_format($profit_totals['quantity'], 2) }}</th>
                            <th class="text-right">{{ number_format($profit_totals['revenue'], 2) }}</th>
                            <th class="text-right">{{ number_format($profit_totals['cost'], 2) }}</th>
                            <th class="text-right">{{ number_format($profit_totals['discount'] ?? 0, 2) }}</th>
                            <th class="text-right {{ $profit_totals['profit'] < 0 ? 'fr-profit-negative' : '' }}">
                                {{ number_format($profit_totals['profit'], 2) }}
                            </th>
                            <th class="text-right {{ $profit_totals['margin'] < 0 ? 'fr-profit-negative' : '' }}">
                                {{ number_format($profit_totals['margin'], 2) }}
                            </th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<style>
    /* Tabs wrap rather than overflowing: there are nine of them, and on a
       laptop they will not fit one line. */
    .fr-profit-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        border-bottom: 1px solid #ddd;
        margin-bottom: 14px;
        padding-left: 0;
    }

    .fr-profit-tabs > li { float: none; }

    .fr-profit-tabs > li > a {
        padding: 7px 14px;
        font-size: 13px;
        white-space: nowrap;
    }

    /*
     * Six columns, five of them figures. The wrapper scrolls rather than the
     * table being squeezed - the failure mode seen repeatedly on the MPCS
     * tables.
     */
    .fr-profit-table-wrap {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .fr-profit-table { min-width: 940px; }

    /* Equal-width digits so the decimal points line up down each column. */
    .fr-profit-table td.text-right,
    .fr-profit-table th.text-right {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum" 1;
    }

    .fr-profit-split {
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .fr-profit-split-hint {
        font-size: 12px;
        color: #777;
    }

    .fr-profit-negative { color: #c0392b; }

    .fr-profit-print-title {
        display: none;
        font-size: 14px;
        font-weight: 700;
        margin: 0 0 8px;
    }

    .fr-profit-total-row th {
        background: #f5f7fa;
        border-top: 2px solid #bbb;
    }
</style>
