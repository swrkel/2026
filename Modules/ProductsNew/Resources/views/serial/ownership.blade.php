@extends('productsnew::layouts.app')
@section('productsnew_content')

<div class="productsnew-page">
    <div class="productsnew-header"><h1>Ownership History</h1><p>Track which customer, branch or service job owns/used each serialized item.</p></div>
    <div class="productsnew-card"><form method="POST" action="{{ route('products-new.ownership.store') }}" class="productsnew-grid productsnew-grid-4">@csrf
        <input name="serial_id" class="form-control" placeholder="Serial ID" required><input name="contact_id" class="form-control" placeholder="Contact ID"><input name="owner_name" class="form-control" placeholder="Owner Name"><input name="owner_mobile" class="form-control" placeholder="Mobile"><select name="ownership_type" class="form-control"><option value="customer">Customer</option><option value="business_location">Business Location</option><option value="service_job">Service Job</option></select><input type="date" name="started_at" class="form-control"><input type="date" name="ended_at" class="form-control"><button class="btn productsnew-btn-primary">Record</button>
    </form></div>
    <div class="productsnew-card"><table class="table table-bordered"><thead><tr><th>Serial</th><th>Owner</th><th>Type</th><th>Start</th><th>End</th><th>Note</th></tr></thead><tbody>@foreach($histories as $h)<tr><td>{{ $h->serial_id }}</td><td>{{ $h->owner_name ?: $h->contact_id }}</td><td>{{ $h->ownership_type }}</td><td>{{ $h->started_at }}</td><td>{{ $h->ended_at }}</td><td>{{ $h->note }}</td></tr>@endforeach</tbody></table>{{ $histories->links() }}</div>
</div>

@endsection
