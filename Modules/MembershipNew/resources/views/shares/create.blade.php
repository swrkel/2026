@extends('membershipnew::layouts.app')
@php
    $title = 'Create Member Shares';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.shares.store') }}">
        @csrf
        <div class="mn-form-grid">
            <input name="linked_business_id" placeholder="Linked Business ID">
            <input name="outlet_business_id" placeholder="Outlet Business ID">
            <input name="location_id" placeholder="Location ID">
            <input name="category_id" placeholder="Category ID">
            <input name="member_id" placeholder="Member ID">
            <input name="shares" placeholder="Shares">
            <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="total_dividend_amount" placeholder="Dividend Amount">
            <input name="name" placeholder="Name">
            <textarea name="note" placeholder="Note"></textarea>
        </div>
        <button type="submit" class="mn-btn mn-btn-success">Save</button>
    </form>
</div>
@endsection
