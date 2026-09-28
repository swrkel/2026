@extends('productsnew::layouts.app')
@section('productsnew_page_title', 'Opening Stock')
@section('productsnew_page_subtitle', 'Create location-controlled opening stock sessions and post lines manually or by CSV import.')
@section('productsnew_content')
<div class="pn-grid-2">
    <section class="pn-card">
        <div class="pn-card-header">
            <div><strong>Opening Stock Sessions</strong><span class="pn-muted">Existing sessions for the current business</span></div>
            <a class="pn-btn pn-btn-light" href="{{ route('products-new.opening-stock.import.template') }}"><i class="fa fa-download"></i> CSV Template</a>
        </div>
        <div class="pn-card-body table-responsive">
            <table class="table pn-table">
                <thead><tr><th>Reference</th><th>Date</th><th>Location</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><strong>{{ $row->reference_no }}</strong></td>
                        <td>{{ $row->session_date }}</td>
                        <td>{{ $row->location_name ?: 'Not selected' }}</td>
                        <td><span class="pn-badge">{{ ucfirst($row->status) }}</span></td>
                        <td class="text-right"><a class="pn-btn pn-btn-primary pn-btn-sm" href="{{ route('products-new.opening-stock.show',$row->id) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="pn-empty-state">No opening stock sessions have been created.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        </div>
    </section>

    <aside class="pn-card">
        <div class="pn-card-header"><div><strong>New Opening Stock Session</strong><span class="pn-muted">A location is required for CSV import</span></div></div>
        <div class="pn-card-body">
            <form method="post" action="{{ route('products-new.opening-stock.store') }}">@csrf
                <div class="form-group"><label>Reference No</label><input class="form-control" name="reference_no" placeholder="Generated automatically when blank"></div>
                <div class="form-group"><label>Date</label><input class="form-control" type="date" name="session_date" value="{{ date('Y-m-d') }}"></div>
                <div class="form-group"><label>Location <span class="text-danger">*</span></label>
                    @php
                        $defaultLocationId = old('location_id') ?: optional($locations->first())->id;
                    @endphp
                    <select class="form-control" name="location_id" data-placeholder="Type to search locations" required>
                        <option value="">Select Location</option>
                        @foreach($locations as $location)<option value="{{ $location->id }}" @selected((string) $defaultLocationId === (string) $location->id)>{{ $location->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
                <button class="pn-btn pn-btn-success"><i class="fa fa-plus"></i> Create Session</button>
            </form>
        </div>
    </aside>
</div>
@endsection
