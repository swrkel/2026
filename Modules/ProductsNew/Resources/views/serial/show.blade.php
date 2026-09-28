@extends('productsnew::layouts.app')
@section('productsnew_content')

<div class="productsnew-page">
    <div class="productsnew-header"><h1>Serial 360° View</h1><p>{{ $serial->serial_no }} / {{ $serial->imei_no }}</p></div>
    <div class="productsnew-card">
        <form method="POST" action="{{ route('products-new.serial.status',$serial) }}" class="productsnew-grid productsnew-grid-4">@csrf
            <select name="status" class="form-control"><option value="available">Available</option><option value="sold">Sold</option><option value="reserved">Reserved</option><option value="service">Service</option><option value="damaged">Damaged</option></select>
            <input name="location_id" class="form-control" placeholder="Location ID">
            <input name="note" class="form-control" placeholder="Note">
            <button class="btn productsnew-btn-primary">Update Status</button>
        </form>
    </div>
    <div class="productsnew-card"><h4>Movement History</h4><table class="table table-bordered"><thead><tr><th>Date</th><th>Type</th><th>From</th><th>To</th><th>Note</th></tr></thead><tbody>@foreach($movements as $m)<tr><td>{{ $m->created_at }}</td><td>{{ $m->movement_type }}</td><td>{{ $m->from_location_id }}</td><td>{{ $m->to_location_id }}</td><td>{{ $m->note }}</td></tr>@endforeach</tbody></table>{{ $movements->links() }}</div>
</div>

@endsection
