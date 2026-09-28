@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="pn-page-title">
    <div>
        <h3>Import / Export Centre</h3>
        <p>Bulk upload, validate, export and clean Products New data safely.</p>
    </div>
    <div class="pn-actions">
        <a class="btn btn-primary" href="{{ route('products-new.import-export.template') }}">Download Correct Sample CSV</a>
        <a class="btn btn-success" href="{{ route('products-new.import-export.export') }}">Export CSV</a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<div class="row">
    <div class="col-md-5">
        <div class="pn-card">
            <div class="pn-card-header"><strong>Upload Product CSV</strong></div>
            <div class="pn-card-body">
                <form method="POST" action="{{ route('products-new.import-export.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Default Business Location</label>
                        <select name="business_location_id" class="form-control">
                            @forelse($locations as $location)
                                <option value="{{ $location->id }}" @selected((string) $defaultLocationId === (string) $location->id)>
                                    {{ $location->name ?? ('Location #' . $location->id) }}
                                </option>
                            @empty
                                <option value="">No business location found</option>
                            @endforelse
                        </select>
                        <small class="pn-muted">Used when the CSV Business Location cell is blank. A location written in the CSV overrides this default.</small>
                    </div>
                    <div class="form-group">
                        <label>CSV File</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt,text/csv" required>
                    </div>
                    <button class="btn btn-primary">Validate Import</button>
                </form>
                <div class="alert alert-info mt-3 mb-0">
                    <strong>Required columns:</strong> Product Name and SKU.<br>
                    Category, Brand, Unit and Business Location may be blank. If a master name is supplied, it must already exist in the current business.
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="pn-card">
            <div class="pn-card-header"><strong>Recent Import Sessions</strong></div>
            <div class="pn-card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead><tr><th>ID</th><th>File</th><th>Status</th><th>Total</th><th>Valid</th><th>Invalid</th><th>Action</th></tr></thead>
                    <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>{{ $session->id }}</td>
                            <td>{{ $session->file_name }}</td>
                            <td>{{ $session->status }}</td>
                            <td>{{ $session->total_rows }}</td>
                            <td>{{ $session->valid_rows }}</td>
                            <td>{{ $session->invalid_rows }}</td>
                            <td><a class="btn btn-xs btn-info" href="{{ route('products-new.import-export.review', $session->id) }}">Review / Repair</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center pn-muted">No import sessions yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
