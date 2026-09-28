@if(!empty($standaloneTab))
    <div class="supplier-profile-panel supplier-standalone-tab">
@endif
<h4>@lang('suppliers::lang.notes_remarks')</h4>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead><tr><th>@lang('suppliers::lang.heading')</th><th>@lang('suppliers::lang.description')</th><th>@lang('suppliers::lang.created_at')</th></tr></thead>
        <tbody>
            @forelse($notes ?? [] as $note)
                <tr><td>{{ $note['heading'] ?? '-' }}</td><td>{{ $note['description'] ?? '-' }}</td><td>{{ $note['created_at'] ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted">@lang('suppliers::lang.no_records_found')</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(!empty($standaloneTab))
    </div>
@endif
