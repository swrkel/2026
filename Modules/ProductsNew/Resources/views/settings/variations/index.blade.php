@extends('productsnew::layouts.app')
@section('productsnew_content')
@include('productsnew::settings.partials.nav')
<div class="productsnew-grid productsnew-grid-2">
    <div class="productsnew-card"><h3>New Variation Template</h3><form method="POST" action="{{ route('products-new.settings.variations.store') }}" class="productsnew-form">@csrf<label>Name <input name="name" required maxlength="191"></label><button class="productsnew-btn productsnew-btn-primary">Save Variation</button></form></div>
    <div class="productsnew-card"><h3>Variation Templates</h3><form class="productsnew-toolbar" method="GET"><input name="search" value="{{ request('search') }}" placeholder="Search variation"><button class="productsnew-btn">Search</button></form><table class="productsnew-table"><thead><tr><th>Name</th><th>Created</th></tr></thead><tbody>@forelse($variations as $variation)<tr><td>{{ $variation->name }}</td><td>{{ $variation->created_at }}</td></tr>@empty<tr><td colspan="2">No variations found.</td></tr>@endforelse</tbody></table>{{ $variations->links() }}</div>
</div>
@endsection
