@if(!empty($standaloneTab))
    <div class="supplier-profile-panel supplier-standalone-tab">
@endif
<h4>@lang('suppliers::lang.audit_trail')</h4>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead><tr><th>@lang('suppliers::lang.description')</th><th>@lang('suppliers::lang.date_time')</th></tr></thead>
        <tbody>
            @forelse($auditTrail ?? [] as $audit)
                <tr><td>{{ $audit['description'] ?? '-' }}</td><td>{{ $audit['created_at'] ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="2" class="text-center text-muted">@lang('suppliers::lang.no_records_found')</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(!empty($standaloneTab))
    </div>
@endif
