@extends('banking-core-teller::layouts.app')
@section('module-content')
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between mb-3"><h5>Slips</h5><a href="{{ url()->current() }}/create" class="btn btn-success">Add New</a></div>
    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody>
    @forelse($items ?? [] as $item)<tr><td>{{ $item->id }}</td><td>{{ $item->status ?? ($item->name ?? '-') }}</td><td>{{ optional($item->created_at)->format('Y-m-d H:i') }}</td><td><a class="btn btn-sm btn-primary" href="{{ url()->current().'/'.$item->id }}">View</a></td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No records yet.</td></tr>@endforelse
    </tbody></table></div>
    @if(method_exists($items ?? null, 'links')) {{ $items->links() }} @endif
</div></div>
@endsection
