@extends('leadsnew::layouts.app')
@section('title', __('leadsnew::messages.followups'))
@section('leadsnew_content')
<section class="content-header leads-new-header"><h1><i class="fa fa-calendar-check-o"></i> {{ __('leadsnew::messages.followups') }}</h1></section>
<section class="content leads-new-page">
    @include('leadsnew::partials.standard_toolbar', ['showDateRange' => true, 'createUrl' => null])
    <div class="ln-panel">
        <div class="ln-panel-header"><h3 class="ln-panel-title">{{ __('leadsnew::messages.followups') }}</h3></div>
        <div class="ln-table table-responsive">
            <table class="table table-hover table-striped">
                <thead><tr><th>#</th><th>{{ __('leadsnew::messages.leads') }}</th><th>{{ __('leadsnew::messages.followup_date') }}</th><th>{{ __('leadsnew::messages.status') }}</th><th>{{ __('leadsnew::messages.notes') }}</th></tr></thead>
                <tbody>
                    @forelse($followups ?? [] as $row)
                        <tr><td>{{ $row->id }}</td><td>{{ optional($row->lead)->name ?? '-' }}</td><td>{{ $row->followup_at ?? $row->date ?? '-' }}</td><td>{{ $row->status ?? '-' }}</td><td>{{ $row->note ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="5"><div class="ln-empty"><i class="fa fa-calendar-o"></i>{{ __('leadsnew::messages.no_records_found') }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($followups) && method_exists($followups, 'links'))<div class="ln-panel-body">{{ $followups->links() }}</div>@endif
    </div>
</section>
@endsection
@section('css')<link rel="stylesheet" href="{{ asset('Modules/LeadsNew/Resources/assets/css/leads_new.css') }}">@endsection
