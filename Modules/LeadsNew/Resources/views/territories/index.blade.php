@extends('leadsnew::layouts.app')
@section('title', 'Territories')
@section('leadsnew_subtitle', 'Maintain the sales and service territories used to organize lead ownership and follow-up work.')
@section('leadsnew_content')
<div class="ln-split-grid">
    <div class="ln-panel">
        <div class="ln-panel-header"><div><h3 class="ln-panel-title"><i class="fa fa-map-marker"></i> Territory Register</h3><div class="ch-card-subtitle">Available territories for this module.</div></div><span class="ln-badge">{{ method_exists($territories, 'total') ? $territories->total() : count($territories) }} territories</span></div>
        <div class="ln-table table-responsive">
            <table class="table table-hover"><thead><tr><th>Name</th><th>Code</th><th>Description</th><th>Status</th></tr></thead><tbody>
            @forelse($territories as $territory)
                <tr><td><strong>{{ $territory->name }}</strong></td><td>{{ $territory->code ?: '-' }}</td><td>{{ $territory->description ?: '-' }}</td><td><span class="ln-badge {{ $territory->is_active ? 'status-converted' : 'status-lost' }}">{{ $territory->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
            @empty
                <tr><td colspan="4"><div class="ln-empty"><i class="fa fa-map-o"></i>No territories created yet.</div></td></tr>
            @endforelse
            </tbody></table>
        </div>
        @if(method_exists($territories, 'links'))<div class="ln-panel-body">{{ $territories->links() }}</div>@endif
    </div>
    <div class="ln-panel ln-sticky-panel">
        <div class="ln-panel-header"><h3 class="ln-panel-title"><i class="fa fa-plus-circle"></i> New Territory</h3></div>
        <div class="ln-panel-body">
            <form method="post" action="{{ url('/leads-new/territories') }}">@csrf
                <div class="form-group"><label>Territory Name</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
                <div class="form-group"><label>Code</label><input type="text" name="code" class="form-control" value="{{ old('code') }}"></div>
                <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea></div>
                <input type="hidden" name="is_active" value="1">
                <button class="btn btn-primary btn-block"><i class="fa fa-save"></i> Save Territory</button>
            </form>
        </div>
    </div>
</div>
@endsection
