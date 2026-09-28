<div class="stn-card stn-command-panel">
    <div class="stn-card-header"><strong>{{ $title }}</strong><a class="stn-link" href="{{ route($action) }}">Open</a></div>
    <div class="stn-card-body">
        @forelse($items as $transfer)
            <div class="stn-mini-row">
                <div><a href="{{ route('stock-transfer-new.transfers.show',$transfer->id) }}"><strong>{{ $transfer->transfer_no }}</strong></a><small>{{ optional($transfer->transfer_date)->format('Y-m-d') }} | Lines: {{ $transfer->lines_count ?? 0 }}</small></div>
                <span class="stn-status stn-status-{{ $transfer->status }}">{{ ucwords(str_replace('_',' ',$transfer->status)) }}</span>
            </div>
        @empty
            <p class="stn-muted">{{ $empty }}</p>
        @endforelse
    </div>
</div>
