@php
    $rows = data_get($workspaceData, 'rows', collect());
    $pumpCards = data_get($workspaceData, 'pump_cards', collect());
    $totals = data_get($workspaceData, 'totals', []);
@endphp

<div class="pdn-daily-status-legend no-print">
    <span><i class="pdn-status-dot available"></i> Available / Closed</span>
    <span><i class="pdn-status-dot assigned"></i> Assigned / Waiting to Receive</span>
    <span><i class="pdn-status-dot received"></i> Received</span>
</div>

<div class="pdn-daily-pump-card-grid">
    @forelse($pumpCards as $pump)
        <div class="pdn-daily-pump-card {{ $pump->status_key }}">
            <strong>{{ $pump->pump_no }}</strong>
            @if($pump->operator_name)
                <span>{{ $pump->operator_name }}</span>
                <small>Shift No: {{ $pump->shift_number ?: '—' }}</small>
            @else
                <span>Available</span>
                @if($pump->product_name)<small>{{ $pump->product_name }}</small>@endif
            @endif
        </div>
    @empty
        <div class="pdn-card pdn-empty">No pumps are configured for the active business location.</div>
    @endforelse
</div>

<div class="pdn-operator-summary-grid pdn-daily-status-summary">
    <div class="pdn-operator-summary-card"><span>Available</span><strong>{{ $totals['available'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card warning"><span>Assigned</span><strong>{{ $totals['assigned'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card purple"><span>Received</span><strong>{{ $totals['received'] ?? 0 }}</strong></div>
    <div class="pdn-operator-summary-card success"><span>Closed</span><strong>{{ $totals['closed'] ?? 0 }}</strong></div>
</div>

<div class="pdn-card">
    <div class="pdn-page-head pdn-daily-status-head">
        <div>
            <h3>All Your Daily Collection</h3>
            <p>Pump assignments and meter activity from Pumper Dashboard-New for the selected period.</p>
        </div>
        <div class="pdn-actions no-print">
            @can('petro_pd_new.operators.manage')
                <a class="pdn-btn primary" href="{{ route('petro-pd-new.operators.daily-status.assign') }}" data-pdn-operator-modal>
                    <i class="fa fa-plus"></i> Assign Pumps
                </a>
            @endcan
            <button class="pdn-btn success" type="button" data-pdn-daily-export="csv" data-table-id="pdn-daily-pump-status-table">
                <i class="fa fa-file-text-o"></i> CSV
            </button>
            <button class="pdn-btn warning" type="button" data-pdn-daily-export="excel" data-table-id="pdn-daily-pump-status-table">
                <i class="fa fa-file-excel-o"></i> Excel
            </button>
            <button class="pdn-btn light" type="button" data-pdn-daily-print="pdn-daily-pump-status-table">
                <i class="fa fa-print"></i> Print
            </button>
            <details class="pdn-column-menu">
                <summary class="pdn-btn purple"><i class="fa fa-columns"></i> Column Visibility</summary>
                <div class="pdn-popover">
                    @foreach(['Action','Date','Shift Number','Settlement No','Pump Operator','Pump No','Starting Meter','Closing Meter','Sold Ltr','Testing Ltr','Sold Amount'] as $columnIndex => $columnLabel)
                        <label class="pdn-check">
                            <input type="checkbox" checked data-pdn-column-toggle="pdn-daily-pump-status-table" data-column-index="{{ $columnIndex }}">
                            {{ $columnLabel }}
                        </label>
                    @endforeach
                </div>
            </details>
        </div>
    </div>

    <div class="pdn-table-wrap">
        <table class="pdn-table pdn-daily-status-table" id="pdn-daily-pump-status-table">
            <thead>
                <tr>
                    <th class="notexport">Action</th>
                    <th>Date</th>
                    <th>Shift Number</th>
                    <th>Settlement No</th>
                    <th>Pump Operator</th>
                    <th>Pump No</th>
                    <th class="amount">Starting Meter</th>
                    <th class="amount">Closing Meter</th>
                    <th class="amount">Sold Ltr</th>
                    <th class="amount">Testing Ltr</th>
                    <th class="amount">Sold Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            <button type="button" class="pdn-btn small primary" data-pdn-operator-actions="daily-status-{{ $row->id }}">
                                Actions <i class="fa fa-caret-down"></i>
                            </button>
                            <template data-pdn-operator-actions-template="daily-status-{{ $row->id }}">
                                <div class="pdn-action-group-title"><i class="fa fa-tint"></i> PUMP ASSIGNMENT</div>
                                @if(Route::has('pumper-dashboard-new.admin.shifts.show'))
                                    <a href="{{ route('pumper-dashboard-new.admin.shifts.show', $row->shift_id) }}">
                                        <i class="fa fa-eye"></i> View Shift
                                    </a>
                                @endif
                                @can('petro_pd_new.operators.manage')
                                    @if($row->can_edit_assignment)
                                        <a href="{{ route('petro-pd-new.operators.daily-status.edit', $row->id) }}" data-pdn-operator-modal>
                                            <i class="fa fa-pencil-square-o"></i> Edit Assignment
                                        </a>
                                    @else
                                        <span class="disabled" title="Editing is disabled after the pump operator receives the assignment or the shift is closed.">
                                            <i class="fa fa-ban"></i> Edit Assignment
                                        </span>
                                    @endif

                                    @if($row->can_cancel_assignment)
                                        <a href="{{ route('petro-pd-new.operators.daily-status.cancel', $row->id) }}" class="danger" data-pdn-operator-modal>
                                            <i class="fa fa-trash"></i> Cancel Assignment
                                        </a>
                                    @else
                                        <span class="disabled" title="Only an unreceived assignment can be cancelled.">
                                            <i class="fa fa-ban"></i> Cancel Assignment
                                        </span>
                                    @endif
                                @endcan
                            </template>
                        </td>
                        <td>{{ $row->date_and_time ? \Carbon\Carbon::parse($row->date_and_time)->format('Y-m-d H:i') : '—' }}</td>
                        <td>
                            <strong>{{ $row->shift_number ?: '—' }}</strong><br>
                            <span class="pdn-badge {{ $row->shift_status }}">{{ ucfirst($row->shift_status) }}</span>
                        </td>
                        <td>{{ $row->settlement_no ?: '—' }}</td>
                        <td>
                            <strong>{{ $row->operator_name }}</strong><br>
                            <small>{{ $row->location_name }}</small>
                        </td>
                        <td>
                            <strong>{{ $row->pump_no }}</strong><br>
                            <span class="pdn-badge {{ $row->status_key }}">{{ $row->status_label }}</span>
                        </td>
                        <td class="amount">{{ number_format((float)$row->starting_meter, 3) }}</td>
                        <td class="amount">{{ $row->closing_meter !== null ? number_format((float)$row->closing_meter, 3) : '—' }}</td>
                        <td class="amount" data-export-number="{{ $row->sold_ltr }}">{{ number_format((float)$row->sold_ltr, 3) }}</td>
                        <td class="amount" data-export-number="{{ $row->testing_ltr }}">{{ number_format((float)$row->testing_ltr, 3) }}</td>
                        <td class="amount" data-export-number="{{ $row->sold_amount }}">{{ number_format((float)$row->sold_amount, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="pdn-empty">No daily pump status records were found for the selected filters.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="8" class="amount">Totals</th>
                    <th class="amount">{{ number_format((float)($totals['sold_ltr'] ?? 0), 3) }}</th>
                    <th class="amount">{{ number_format((float)($totals['testing_ltr'] ?? 0), 3) }}</th>
                    <th class="amount">{{ number_format((float)($totals['sold_amount'] ?? 0), 4) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
