@extends('bankingmicrofinance::layouts.app')
@section('content')
<div class="container-fluid bkg-mfi-page"><h3>{{ $title ?? 'Banking Microfinance' }}</h3>@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class='d-flex justify-content-between mb-3'><h4>Group Meetings</h4><a class='btn btn-primary' href='{{ url()->current().'/create' }}'>Add New</a></div><table class='table table-bordered table-striped'><thead><tr><th>Group</th><th>Date</th><th>Time</th><th>Venue</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($items as $item)<tr><td>{{ $item->group_id }}</td><td>{{ $item->meeting_date }}</td><td>{{ $item->meeting_time }}</td><td>{{ $item->venue }}</td><td>{{ ucfirst($item->status) }}</td><td><a class='btn btn-sm btn-info' href='{{ url()->current().'/'.$item->id.'/edit' }}'>Edit</a></td></tr>@empty<tr><td colspan='9'>No records found.</td></tr>@endforelse</tbody></table>{{ $items->links() }}
</div>@endsection
