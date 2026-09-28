@extends('stocktakingnew::layouts.app')

@section('stk_title', ($session->status === 'recount' ? 'Recount Sheet – ' : 'Count Sheet – ') . $session->stock_take_no)
@section('stk_subtitle', $locationName . ' / ' . $storeName . ' • ' . ucfirst($session->count_mode) . ' count')

@section('stk_content')
<div class="stk-count-toolbar">
    <form method="get">
        <div class="stk-search">
            <i class="fa fa-search"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Scan barcode or search SKU / product">
        </div>
        <select name="state" class="form-control">
            <option value="">All Lines</option>
            <option value="pending" @selected(request('state') === 'pending')>Pending</option>
            <option value="counted" @selected(request('state') === 'counted')>Counted</option>
            <option value="recount" @selected(request('state') === 'recount')>Recount Required</option>
        </select>
        <button class="stk-btn stk-btn-primary">Filter</button>
    </form>
    @can('stock_taking_new.counts.import')
        <div>
            <a href="{{ route('stock-taking-new.import.template', $session) }}" class="stk-btn stk-btn-light">
                <i class="fa fa-download"></i> CSV Template
            </a>
            <button class="stk-btn stk-btn-light" data-toggle="modal" data-target="#importModal">
                <i class="fa fa-upload"></i> Import
            </button>
        </div>
    @endcan
</div>

<form method="post"
      action="{{ $session->status === 'recount' ? route('stock-taking-new.counts.recount', $session) : route('stock-taking-new.counts.save', $session) }}"
      id="stk-count-form">
    @csrf
    <section class="stk-card">
        <div class="table-responsive">
            <table class="stk-table stk-count-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>SKU / Barcode</th>
                        <th>Product</th>
                        @if($session->count_mode === 'open')<th>System Qty</th>@endif
                        <th>Counted Qty</th>
                        @if($session->count_mode === 'open')<th>Variance</th>@endif
                        <th>Bin</th>
                        <th>Notes</th>
                        <th>State</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $index => $line)
                        <tr class="{{ $line->requires_recount ? 'stk-recount-row' : '' }}">
                            <td>
                                {{ $lines->firstItem() + $index }}
                                <input type="hidden" name="counts[{{ $index }}][line_id]" value="{{ $line->id }}">
                            </td>
                            <td><strong>{{ $line->sku ?: '—' }}</strong><small>{{ $line->barcode }}</small></td>
                            <td><strong>{{ $line->product_name }}</strong>@if($line->batch_no)<small>Batch: {{ $line->batch_no }}</small>@endif</td>
                            @if($session->count_mode === 'open')
                                <td class="text-right"><span class="stk-system-qty">{{ number_format((float) $line->system_qty, 4) }}</span></td>
                            @endif
                            <td>
                                <input type="number" step="0.0001" name="counts[{{ $index }}][counted_qty]"
                                       value="{{ old('counts.' . $index . '.counted_qty', $line->final_count_qty) }}"
                                       class="form-control stk-count-input"
                                       @if($session->count_mode === 'open') data-system="{{ (float) $line->system_qty }}" @endif required>
                            </td>
                            @if($session->count_mode === 'open')
                                <td class="text-right"><strong class="stk-live-variance">{{ number_format((float) $line->variance_qty, 4) }}</strong></td>
                            @endif
                            <td><input name="counts[{{ $index }}][bin_location]" value="{{ old('counts.' . $index . '.bin_location', $line->bin_location) }}" class="form-control"></td>
                            <td><input name="counts[{{ $index }}][notes]" value="{{ old('counts.' . $index . '.notes', $line->notes) }}" class="form-control"></td>
                            <td>@include('stocktakingnew::partials.status', ['status' => $line->count_status])</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $session->count_mode === 'open' ? 9 : 7 }}" class="stk-empty">
                            {{ $session->status === 'recount' ? 'No recount lines remain.' : 'No lines match the filter.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="stk-pagination">{{ $lines->links() }}</div>
    </section>

    <div class="stk-sticky-actions">
        <a href="{{ route('stock-taking-new.sessions.show', $session) }}" class="stk-btn stk-btn-light">Back to Session</a>
        @if($lines->count() > 0)
            <button class="stk-btn stk-btn-primary"><i class="fa fa-save"></i> Save {{ $session->status === 'recount' ? 'Recounts' : 'Counts' }}</button>
        @endif
        @can('stock_taking_new.counts.submit')
            @if($session->status === 'counting' && $session->counted_line_count >= $session->line_count)
                <button type="submit" formaction="{{ route('stock-taking-new.counts.submit', $session) }}" class="stk-btn stk-btn-success"
                        onclick="return confirm('{{ $hasRequiredRecounts ? 'Complete initial count and begin required recounts?' : 'Complete and submit all counts?' }}');">
                    <i class="fa fa-check"></i> {{ $hasRequiredRecounts ? 'Start Required Recount' : 'Complete Count' }}
                </button>
            @elseif($session->status === 'recount' && ! $hasRequiredRecounts)
                <button type="submit" formaction="{{ route('stock-taking-new.counts.submit', $session) }}" class="stk-btn stk-btn-success"
                        onclick="return confirm('Complete the recount and submit the final quantities?');">
                    <i class="fa fa-check"></i> Complete Recount
                </button>
            @endif
        @endcan
    </div>
</form>

@can('stock_taking_new.counts.import')
    <div class="modal fade" id="importModal">
        <div class="modal-dialog">
            <form method="post" enctype="multipart/form-data" action="{{ route('stock-taking-new.import.store', $session) }}" class="modal-content">
                @csrf
                <div class="modal-header"><h4>Import Count CSV</h4></div>
                <div class="modal-body">
                    <input type="file" name="count_file" accept=".csv,.txt" class="form-control" required>
                    <p class="help-block">Use line_id and counted_qty from the downloadable template.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="stk-btn stk-btn-light" data-dismiss="modal">Cancel</button>
                    <button class="stk-btn stk-btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
@endcan
@endsection
