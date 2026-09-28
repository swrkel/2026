@extends('RiceMill::layout')
@section('rcm-title','Milling / Production Details')
@section('rcm-actions')
    <a class="rcm-btn" href="{{ route('rice-mill.production.index') }}">
        <i class="fa fa-list"></i> Production History
    </a>
    @if(in_array($batch->status,['draft','in_progress']))
        <a class="rcm-btn" href="{{ route('rice-mill.production.complete-form',$batch->id) }}">
            <i class="fa fa-play"></i> Continue Operation
        </a>
    @endif
@endsection

@section('rcm-content')
@php
    $statusLabel = ucwords(str_replace('_',' ',(string)$batch->status));
    $outputLabels = array_merge([
        'rice' => 'Rice',
        'broken_rice' => 'Broken Rice',
        'bran' => 'Bran',
        'husk' => 'Husk',
        'other' => 'Other',
    ], $outputLabels ?? []);
@endphp

<div class="rcm-card">
    <div class="rcm-section-title">Milling Batch Details</div>
    <div class="rcm-form-grid">
        <div class="rcm-field">
            <label>Batch No.</label>
            <div class="rcm-readonly-value">{{ $batch->batch_no ?: '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Status</label>
            <div class="rcm-readonly-value">{{ $statusLabel ?: '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Mill</label>
            <div class="rcm-readonly-value">{{ $millName ?: '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Started At</label>
            <div class="rcm-readonly-value">{{ $batch->started_at ? $batch->started_at->format('Y-m-d H:i:s') : '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Completed At</label>
            <div class="rcm-readonly-value">{{ $batch->completed_at ? $batch->completed_at->format('Y-m-d H:i:s') : '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Location</label>
            <div class="rcm-readonly-value">{{ $locationName ?: '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Store</label>
            <div class="rcm-readonly-value">{{ $storeName ?: '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Created By</label>
            <div class="rcm-readonly-value">{{ $userNames[(int)$batch->created_by] ?? '-' }}</div>
        </div>
        <div class="rcm-field">
            <label>Completed By</label>
            <div class="rcm-readonly-value">{{ $userNames[(int)$batch->completed_by] ?? '-' }}</div>
        </div>
    </div>

    <div class="rcm-field" style="margin-top:12px">
        <label>Note</label>
        <div class="rcm-readonly-value rcm-readonly-note">{{ $batch->note ?: '-' }}</div>
    </div>
</div>

<div class="rcm-card">
    <div class="rcm-section-title">Production Summary</div>
    <div class="rcm-summary-grid">
        <div class="rcm-summary-box">
            <span>Input Qty</span>
            <strong>{{ number_format((float)$batch->input_qty,$rcmQuantityPrecision) }} kg</strong>
        </div>
        <div class="rcm-summary-box">
            <span>Rice Output</span>
            <strong>{{ number_format((float)$batch->rice_output_qty,$rcmQuantityPrecision) }} kg</strong>
        </div>
        <div class="rcm-summary-box">
            <span>Total Output</span>
            <strong>{{ number_format((float)$batch->total_output_qty,$rcmQuantityPrecision) }} kg</strong>
        </div>
        <div class="rcm-summary-box">
            <span>Process Loss</span>
            <strong>{{ number_format((float)$batch->process_loss_qty,$rcmQuantityPrecision) }} kg</strong>
        </div>
        <div class="rcm-summary-box">
            <span>Rice Yield</span>
            <strong>{{ number_format((float)$batch->rice_yield_percent,2) }}%</strong>
        </div>
        <div class="rcm-summary-box">
            <span>Production Cost</span>
            <strong>{{ number_format((float)$batch->production_cost,$rcmCurrencyPrecision) }}</strong>
        </div>
        <div class="rcm-summary-box">
            <span>Cost / Kg</span>
            <strong>{{ number_format((float)$batch->cost_per_kg,$rcmCurrencyPrecision) }}</strong>
        </div>
    </div>
</div>

<div class="rcm-card">
    <div class="rcm-section-title">Paddy Inputs</div>
    <div class="rcm-table-wrap">
        <table class="rcm-table">
            <thead>
                <tr>
                    <th>Paddy Lot</th>
                    <th>Paddy Variety</th>
                    <th class="rcm-num">Quantity (kg)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inputs as $input)
                    <tr>
                        <td>{{ $input->lot_no ?: ('Lot #'.$input->paddy_lot_id) }}</td>
                        <td>
                            @if($input->paddy_name)
                                {{ $input->paddy_code ? $input->paddy_code.' - ' : '' }}{{ $input->paddy_name }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="rcm-num">{{ number_format((float)$input->quantity,$rcmQuantityPrecision) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="rcm-muted">No paddy input rows recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rcm-card">
    <div class="rcm-section-title">Outputs</div>
    <div class="rcm-table-wrap">
        <table class="rcm-table">
            <thead>
                <tr>
                    <th>Output Type</th>
                    <th>Rice Product</th>
                    <th class="rcm-num">Quantity (kg)</th>
                    <th class="rcm-num">Unit Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outputs as $output)
                    <tr>
                        <td>{{ $outputLabels[$output->output_type] ?? ucwords(str_replace('_',' ',$output->output_type)) }}</td>
                        <td>
                            @if($output->product_name)
                                {{ $output->product_code ? $output->product_code.' - ' : '' }}{{ $output->product_name }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="rcm-num">{{ number_format((float)$output->quantity,$rcmQuantityPrecision) }}</td>
                        <td class="rcm-num">{{ number_format((float)$output->unit_cost,$rcmCurrencyPrecision) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="rcm-muted">No output rows recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rcm-card">
    <div class="rcm-section-title">Production Costs</div>
    <div class="rcm-table-wrap">
        <table class="rcm-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Cost Type</th>
                    <th class="rcm-num">Amount</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($costs as $cost)
                    <tr>
                        <td>{{ $cost->cost_date ?: '-' }}</td>
                        <td>{{ $cost->cost_type }}</td>
                        <td class="rcm-num">{{ number_format((float)$cost->amount,$rcmCurrencyPrecision) }}</td>
                        <td>{{ $cost->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="rcm-muted">No production cost rows recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
