<section class="content-header">
    <h1>{{ $title }}</h1>
    <small>{{ $subtitle }}</small>
</section>
<section class="content customers-report-page">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title }}</h3>
            <div class="box-tools pull-right"><a href="{{ route('customers.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> @lang('messages.back')</a></div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        @if($type === 'documents')
                            <tr>
                                <td>{{ $row->customer_name }}</td><td>{{ $row->title ?? $row->document_title ?? '' }}</td><td>{{ $row->category_name }}</td><td>{{ $row->issue_date }}</td><td>{{ $row->expiry_date }}</td><td>{{ $row->status }}</td>
                            </tr>
                        @elseif($type === 'attachments')
                            <tr>
                                <td>{{ !empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '' }}</td><td>{{ $row->customer_name }}</td><td>{{ $row->file_name ?? $row->original_name ?? '' }}</td><td>{{ $row->user_name }}</td><td>{{ $row->remarks ?? $row->note ?? '' }}</td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ !empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '' }}</td><td>{{ $row->customer_name }}</td><td>{{ $row->action ?? $row->activity_type ?? '' }}</td><td>{{ $row->user_name }}</td><td>{{ $row->description ?? $row->remarks ?? '' }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="{{ count($headers) }}" class="text-center text-muted">@lang('customers::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
