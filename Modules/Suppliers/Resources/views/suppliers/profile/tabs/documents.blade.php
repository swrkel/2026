<div class="supplier-profile-panel">
    <h4>@lang('suppliers::lang.documents_attachments')</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>@lang('suppliers::lang.name')</th><th>@lang('suppliers::lang.file_name')</th><th>@lang('suppliers::lang.created_at')</th></tr></thead>
            <tbody>
                @forelse($documents ?? [] as $document)
                    <tr><td>{{ $document['display_name'] ?? '-' }}</td><td>{{ $document['file_name'] ?? '-' }}</td><td>{{ $document['created_at'] ?? '-' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">@lang('suppliers::lang.no_records_found')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
