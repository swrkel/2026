@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.staff'))
@section('content')
<div class="rn-page rn-staff-page">
    <div class="rn-header-card">
        <div>
            <h3>{{ __('restaurantnew::lang.staff') }}</h3>
            <p>{{ __('restaurantnew::lang.staff_subtitle') }}</p>
        </div>
    </div>

    <div class="rn-card">
        <form method="POST" action="{{ route('restaurantnew.staff.store') }}" class="rn-grid-form">
            @csrf
            <input type="hidden" name="business_id" value="{{ session('business.id') }}">
            <div><label>Name</label><input name="name" class="form-control" required></div>
            <div><label>Code</label><input name="staff_code" class="form-control"></div>
            <div><label>Role</label><select name="role" class="form-control"><option value="waiter">Waiter</option><option value="cashier">Cashier</option><option value="kitchen">Kitchen</option><option value="manager">Manager</option></select></div>
            <div><label>Service %</label><input name="service_charge_share_percent" type="number" step="0.0001" class="form-control" value="0"></div>
            <div class="rn-check"><label><input type="checkbox" name="can_take_orders" value="1"> Can take orders</label></div>
            <div class="rn-check"><label><input type="checkbox" name="can_cashier" value="1"> Can cashier</label></div>
            <div><button class="btn btn-primary rn-btn">Save Staff</button></div>
        </form>
    </div>

    <div class="rn-card">
        <table class="table table-bordered table-striped rn-datatable">
            <thead><tr><th>Code</th><th>Name</th><th>Role</th><th>Service %</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($staff as $member)
                <tr><td>{{ $member->staff_code }}</td><td>{{ $member->name }}</td><td>{{ ucfirst($member->role) }}</td><td>{{ number_format($member->service_charge_share_percent, 4) }}</td><td>{{ $member->is_active ? 'Active' : 'Inactive' }}</td></tr>
            @endforeach
            </tbody>
        </table>
        {{ $staff->links() }}
    </div>
</div>
@endsection
