@php($rows = data_get($workspaceData, 'rows', collect()))
@php($totals = data_get($workspaceData, 'totals', []))
@php($money = static fn ($value) => number_format((float) $value, 4, '.', ','))
@php($qty = static fn ($value) => number_format((float) $value, 3, '.', ','))

<div class="pdn-operator-summary-grid">
    <div class="pdn-operator-summary-card"><span>Active Operators</span><strong>{{ $totals['active'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Inactive Operators</span><strong>{{ $totals['inactive'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card"><span>Visible Records</span><strong>{{ $rows->count() }}</strong></div>
</div>

<div class="pdn-card pdn-operator-list-card">
    <div class="pdn-page-head compact pdn-operator-list-head">
        <div>
            <h3>All your Pump Operators</h3>
            <p>Balances, fuel sales, commission, excess and shortage for the selected period.</p>
        </div>
        <div class="pdn-actions no-print">
            <button type="button" class="pdn-btn light" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
            <a class="pdn-btn primary" href="{{ route('petro-pd-new.reports.index', ['report' => 'operators']) }}"><i class="fa fa-download"></i> Export / Report</a>
        </div>
    </div>

    <div class="pdn-table-wrap pdn-operator-table-wrap">
        <table class="pdn-table pdn-operator-ledger-table" id="pdn-pump-operators-table">
            <thead>
                <tr>
                    <th class="no-print">Action</th>
                    <th class="amount">Current Balance</th>
                    <th class="amount">Balance for the Period</th>
                    <th>Pump Operator</th>
                    <th>Location</th>
                    <th class="amount">Sold Fuel Qty (Lts)</th>
                    <th class="amount">Sale Amount Fuel</th>
                    <th>Commission Type</th>
                    <th class="amount">Commission Rate</th>
                    <th class="amount">Commission Amount</th>
                    <th class="amount">Excess Amount</th>
                    <th class="amount">Short Amount</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td class="no-print pdn-action-cell">
                        <button type="button" class="pdn-btn small primary pdn-operator-actions-button" data-pdn-operator-actions="{{ $row->id }}">
                            Actions <i class="fa fa-caret-down"></i>
                        </button>
                        <div class="pdn-operator-actions-template" data-pdn-operator-actions-template="{{ $row->id }}" hidden>
                            <div class="pdn-action-section-title"><i class="fa fa-user"></i> Operator</div>
                            <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'view']) }}" data-pdn-operator-modal><i class="fa fa-eye"></i> View</a>
                            @can('petro_pd_new.operators.manage')
                                <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'edit']) }}" data-pdn-operator-modal><i class="fa fa-pencil-square-o"></i> Edit</a>
                                <form method="post" action="{{ route('petro-pd-new.operators.toggle', $row->id) }}" data-confirm="{{ $row->status === 'active' ? 'Deactivate this PD Operator?' : 'Activate this PD Operator?' }}">
                                    @csrf
                                    <button type="submit"><i class="fa {{ $row->status === 'active' ? 'fa-times' : 'fa-check' }}"></i> {{ $row->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            @endcan

                            <div class="pdn-action-divider"></div>
                            <div class="pdn-action-section-title"><i class="fa fa-money"></i> Payments</div>
                            @can('petro_pd_new.operators.manage')
                                <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'commission']) }}" data-pdn-operator-modal><i class="fa fa-plus-circle"></i> Pay Excess &amp; Commission</a>
                                <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'recovery']) }}" data-pdn-operator-modal><i class="fa fa-minus-circle"></i> Recover Shortages</a>
                            @endcan

                            <div class="pdn-action-divider"></div>
                            <div class="pdn-action-section-title"><i class="fa fa-book"></i> Ledger</div>
                            <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'contact']) }}" data-pdn-operator-modal><i class="fa fa-id-card-o"></i> Contact Info</a>
                            <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'ledger']) }}?date_from={{ $filters['date_from'] }}&date_to={{ $filters['date_to'] }}" data-pdn-operator-modal><i class="fa fa-anchor"></i> Ledger</a>
                            <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'commissions']) }}?date_from={{ $filters['date_from'] }}&date_to={{ $filters['date_to'] }}" data-pdn-operator-modal><i class="fa fa-percent"></i> List Commission</a>
                            <a href="{{ route('petro-pd-new.operators.action', [$row->id, 'documents']) }}" data-pdn-operator-modal><i class="fa fa-paperclip"></i> Documents &amp; Notes</a>
                        </div>
                    </td>
                    <td class="amount">{{ $money($row->current_balance) }}</td>
                    <td class="amount {{ $row->balance_for_period > 0 ? 'pdn-text-danger' : '' }}">{{ $money($row->balance_for_period) }}</td>
                    <td>
                        <strong>{{ $row->display_name }}</strong>
                        @if($row->is_default)<span class="pdn-badge danger">Default</span>@endif
                        @if($row->status !== 'active')<span class="pdn-badge inactive">Deactivated</span>@endif
                        <div class="pdn-muted-text">PD Operator #{{ $row->pone_pd_operator_id ?: '—' }}</div>
                    </td>
                    <td>{{ $row->location_name }}</td>
                    <td class="amount">{{ $qty($row->sold_fuel_qty) }}</td>
                    <td class="amount">{{ $money($row->sale_amount_fuel) }}</td>
                    <td>{{ ucfirst($row->commission_type ?: 'none') }}</td>
                    <td class="amount">{{ $money($row->commission_rate) }}</td>
                    <td class="amount">{{ $money($row->commission_amount) }}</td>
                    <td class="amount">{{ $money($row->excess_amount) }}</td>
                    <td class="amount pdn-text-danger">{{ $money($row->short_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="12" class="pdn-empty">No PD Operators were found for the selected business, location and filters.</td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="1">Total</th>
                    <th class="amount">{{ $money($totals['current_balance'] ?? 0) }}</th>
                    <th class="amount">{{ $money($totals['balance_for_period'] ?? 0) }}</th>
                    <th colspan="2"></th>
                    <th class="amount">{{ $qty($totals['sold_fuel_qty'] ?? 0) }}</th>
                    <th class="amount">{{ $money($totals['sale_amount_fuel'] ?? 0) }}</th>
                    <th colspan="2"></th>
                    <th class="amount">{{ $money($totals['commission_amount'] ?? 0) }}</th>
                    <th class="amount">{{ $money($totals['excess_amount'] ?? 0) }}</th>
                    <th class="amount">{{ $money($totals['short_amount'] ?? 0) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
