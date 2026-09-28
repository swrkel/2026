<div class="box productsnew-card"><div class="box-header with-border"><h3 class="box-title">Attention</h3></div><div class="box-body">
@forelse($workspace['alerts'] as $alert)<div class="alert alert-{{ $alert['level'] }}">{{ $alert['message'] }}</div>@empty<p class="text-muted">No immediate alerts.</p>@endforelse
</div></div>
