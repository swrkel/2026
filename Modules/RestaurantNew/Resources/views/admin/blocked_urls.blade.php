@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::messages.blocked_urls'))
@section('content')
<div class="rn-page">
 <div class="rn-toolbar"><h3>{{ __('restaurantnew::messages.blocked_urls') }}</h3></div>
 <div class="rn-card table-responsive"><table class="table table-bordered rn-datatable"><thead><tr><th>Date</th><th>User</th><th>Route</th><th>URL</th><th>Permission</th><th>Reason</th></tr></thead><tbody>
 @foreach($blocks as $block)<tr><td>{{ $block->created_at }}</td><td>{{ $block->user_id }}</td><td>{{ $block->route_name }}</td><td>{{ $block->url_path }}</td><td>{{ $block->required_permission }}</td><td>{{ $block->block_reason }}</td></tr>@endforeach
 </tbody></table></div>
</div>
@endsection
