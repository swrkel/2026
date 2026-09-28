@extends('stocktakingnew::layouts.app')

@section('stk_title', $session->stock_take_no . ' – ' . $session->title)
@section('stk_subtitle', $locationName . ' / ' . $storeName)

@section('stk_content')
<div class="stk-summary-grid">
    <div><span>Status</span>@include('stocktakingnew::partials.status', ['status' => $session->status])</div>
    <div><span>Count Date</span><strong>{{ optional($session->count_date)->format('d M Y') }}</strong></div>
    <div><span>Method</span><strong>{{ ucfirst($session->count_method) }} / {{ ucfirst($session->count_mode) }}</strong></div>
    <div><span>Progress</span><strong>{{ $session->counted_line_count }} / {{ $session->line_count }}</strong></div>
    @if($canViewSystemQuantity)
        <div><span>Variance Qty</span><strong>{{ number_format((float) $session->variance_qty_total, 4) }}</strong></div>
        <div><span>Variance Value</span><strong>{{ number_format((float) $session->variance_value_total, 4) }}</strong></div>
    @else
        <div><span>Variance Qty</span><strong>Hidden during blind count</strong></div>
        <div><span>Variance Value</span><strong>Hidden during blind count</strong></div>
    @endif
</div>

<div class="stk-action-bar">
    @if($session->status === 'draft')
        @can('stock_taking_new.sessions.prepare')
            <form method="post" action="{{ route('stock-taking-new.sessions.prepare', $session) }}">
                @csrf
                <button class="stk-btn stk-btn-primary"><i class="fa fa-camera"></i> Prepare Snapshot</button>
            </form>
        @endcan
        @can('stock_taking_new.sessions.edit')
            <a href="{{ route('stock-taking-new.sessions.edit', $session) }}" class="stk-btn stk-btn-light">
                <i class="fa fa-edit"></i> Edit
            </a>
        @endcan
    @endif

    @if($session->status === 'prepared')
        @can('stock_taking_new.sessions.start')
            <form method="post" action="{{ route('stock-taking-new.sessions.start', $session) }}">
                @csrf
                <button class="stk-btn stk-btn-success"><i class="fa fa-play"></i> Start Counting</button>
            </form>
        @endcan
    @endif

    @if($session->status === 'counting')
        @can('stock_taking_new.counts.enter')
            <a href="{{ route('stock-taking-new.counts.sheet', $session) }}" class="stk-btn stk-btn-primary">
                <i class="fa fa-calculator"></i> Open Count Sheet
            </a>
        @endcan
    @elseif($session->status === 'recount')
        @can('stock_taking_new.recounts.manage')
            <a href="{{ route('stock-taking-new.counts.sheet', $session) }}" class="stk-btn stk-btn-primary">
                <i class="fa fa-refresh"></i> Open Recount Sheet
            </a>
        @endcan
    @endif

    @if($session->status === 'submitted')
        @can('stock_taking_new.approvals.approve')
            <button class="stk-btn stk-btn-success" data-toggle="modal" data-target="#approveModal">
                <i class="fa fa-check"></i> Approve
            </button>
        @endcan
        @can('stock_taking_new.approvals.reject')
            <button class="stk-btn stk-btn-danger" data-toggle="modal" data-target="#rejectModal">
                <i class="fa fa-times"></i> Reject
            </button>
        @endcan
    @endif

    @if($session->status === 'approved')
        @can('stock_taking_new.reconciliation.post')
            <form method="post" action="{{ route('stock-taking-new.approvals.post', $session) }}"
                  onsubmit="return confirm('Post the final counted quantities to inventory?');">
                @csrf
                <button class="stk-btn stk-btn-success"><i class="fa fa-lock"></i> Post Reconciliation</button>
            </form>
        @endcan
    @endif

    @can('stock_taking_new.documents.print')
        <a href="{{ route('stock-taking-new.documents.print', [$session, 'summary']) }}" target="_blank" class="stk-btn stk-btn-light">
            <i class="fa fa-print"></i> Print
        </a>
        <a href="{{ route('stock-taking-new.documents.pdf', [$session, 'summary']) }}" target="_blank" class="stk-btn stk-btn-light">
            <i class="fa fa-file-pdf-o"></i> PDF
        </a>
    @endcan
    @can('stock_taking_new.documents.share')
        <a href="{{ route('stock-taking-new.shares.create', $session) }}" class="stk-btn stk-btn-purple">
            <i class="fa fa-share-alt"></i> Share
        </a>
    @endcan
</div>

<section class="stk-card">
    <div class="stk-card-header">
        <div>
            <h3><i class="fa fa-list"></i> Product Snapshot</h3>
            <p>{{ number_format($session->line_count) }} products in this session.</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="stk-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>SKU</th>
                    <th>Product</th>
                    @if($canViewSystemQuantity)<th>System Qty</th>@endif
                    <th>Final Count</th>
                    @if($canViewSystemQuantity)<th>Variance Qty</th><th>Unit Cost</th><th>Variance Value</th>@endif
                    <th>Count Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $line)
                    <tr>
                        <td>{{ $line->id }}</td>
                        <td>{{ $line->sku ?: '—' }}</td>
                        <td>
                            <strong>{{ $line->product_name }}</strong>
                            @if($line->bin_location)<small>Bin: {{ $line->bin_location }}</small>@endif
                        </td>
                        @if($canViewSystemQuantity)
                            <td class="text-right">{{ number_format((float) $line->system_qty, 4) }}</td>
                        @endif
                        <td class="text-right">{{ $line->final_count_qty === null ? '—' : number_format((float) $line->final_count_qty, 4) }}</td>
                        @if($canViewSystemQuantity)
                            <td class="text-right {{ $line->variance_qty < 0 ? 'stk-negative' : ($line->variance_qty > 0 ? 'stk-positive' : '') }}">
                                {{ number_format((float) $line->variance_qty, 4) }}
                            </td>
                            <td class="text-right">{{ number_format((float) $line->unit_cost, 4) }}</td>
                            <td class="text-right">{{ number_format((float) $line->variance_value, 4) }}</td>
                        @endif
                        <td>@include('stocktakingnew::partials.status', ['status' => $line->count_status])</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $canViewSystemQuantity ? 9 : 5 }}" class="stk-empty">Prepare the snapshot to load product lines.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="stk-pagination">{{ $lines->links() }}</div>
</section>

@if($session->status === 'submitted')
    @can('stock_taking_new.approvals.approve')
        <div class="modal fade" id="approveModal">
            <div class="modal-dialog">
                <form method="post" action="{{ route('stock-taking-new.approvals.approve', $session) }}" class="modal-content">
                    @csrf
                    <div class="modal-header"><h4>Approve Stock Taking</h4></div>
                    <div class="modal-body"><label>Remarks</label><textarea name="remarks" class="form-control"></textarea></div>
                    <div class="modal-footer">
                        <button type="button" class="stk-btn stk-btn-light" data-dismiss="modal">Cancel</button>
                        <button class="stk-btn stk-btn-success">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('stock_taking_new.approvals.reject')
        <div class="modal fade" id="rejectModal">
            <div class="modal-dialog">
                <form method="post" action="{{ route('stock-taking-new.approvals.reject', $session) }}" class="modal-content">
                    @csrf
                    <div class="modal-header"><h4>Reject Stock Taking</h4></div>
                    <div class="modal-body"><label>Reason *</label><textarea name="remarks" class="form-control" required></textarea></div>
                    <div class="modal-footer">
                        <button type="button" class="stk-btn stk-btn-light" data-dismiss="modal">Cancel</button>
                        <button class="stk-btn stk-btn-danger">Reject</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endif
@endsection
