@extends('productsnew::layouts.app')
@section('productsnew_content')
@include('productsnew::settings.partials.nav')
<div class="productsnew-grid productsnew-grid-2">
    <div class="productsnew-card"><h3>New Unit</h3><form method="POST" action="{{ route('products-new.settings.units.store') }}" class="productsnew-form">@csrf<label>Actual Name <input name="actual_name" required maxlength="191"></label><label>Short Name <input name="short_name" required maxlength="50"></label><label class="productsnew-check"><input type="checkbox" name="allow_decimal" value="1"> Allow Decimal</label><button class="productsnew-btn productsnew-btn-primary">Save Unit</button></form></div>
    <div class="productsnew-card"><h3>Units</h3><form class="productsnew-toolbar" method="GET"><input name="search" value="{{ request('search') }}" placeholder="Search unit"><button class="productsnew-btn">Search</button></form><table class="productsnew-table"><thead><tr><th>Actual Name</th><th>Short Name</th><th>Decimal</th></tr></thead><tbody>@forelse($units as $unit)<tr><td>{{ $unit->actual_name }}</td><td>{{ $unit->short_name }}</td><td>{{ $unit->allow_decimal ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="3">No units found.</td></tr>@endforelse</tbody></table>{{ $units->links() }}</div>
</div>
@endsection
