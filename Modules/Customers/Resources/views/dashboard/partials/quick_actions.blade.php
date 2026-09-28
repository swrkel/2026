<div class="box box-info customers-dashboard-box">
    <div class="box-header with-border">
        <h3 class="box-title">Quick Actions</h3>
    </div>
    <div class="box-body">
        @forelse($quick_actions ?? [] as $link)
            <a href="{{ $link['url'] }}" class="btn btn-default btn-block customers-quick-link">
                <i class="{{ $link['icon'] }}"></i> {{ $link['label'] }}
            </a>
        @empty
            <span class="text-muted">No quick actions available.</span>
        @endforelse
    </div>
</div>
