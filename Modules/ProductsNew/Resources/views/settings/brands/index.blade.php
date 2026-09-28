@extends('productsnew::layouts.app')
@section('productsnew_content')
@include('productsnew::settings.partials.nav')
<div class="productsnew-grid productsnew-grid-2">
    <div class="productsnew-card"><h3>New Brand</h3><form method="POST" action="{{ route('products-new.settings.brands.store') }}" class="productsnew-form">@csrf<label>Name <input name="name" required maxlength="191"></label><label>Description <textarea name="description" maxlength="500"></textarea></label><button class="productsnew-btn productsnew-btn-primary">Save Brand</button></form></div>
    <div class="productsnew-card"><h3>Brands</h3><form class="productsnew-toolbar" method="GET"><input name="search" value="{{ request('search') }}" placeholder="Search brand"><button class="productsnew-btn">Search</button></form><table class="productsnew-table"><thead><tr><th>Name</th><th>Description</th></tr></thead><tbody>@forelse($brands as $brand)<tr><td>{{ $brand->name }}</td><td>{{ $brand->description }}</td></tr>@empty<tr><td colspan="2">No brands found.</td></tr>@endforelse</tbody></table>{{ $brands->links() }}</div>
</div>
@endsection
