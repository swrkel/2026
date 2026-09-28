@extends('membershipnew::layouts.app')
@php
    $title = 'Create Payments';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.payments.store') }}">
        @csrf
        <div class="mn-form-grid">
            <input name="first_name" placeholder="Name / First Name">
            <input name="name" placeholder="Plan Name">
            <input name="payment_ref_no" placeholder="Payment Reference">
            <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="amount" placeholder="Amount">
            <textarea name="note" placeholder="Note"></textarea>
        </div>
        <button class="mn-btn mn-btn-success" type="submit">Save</button>
    </form>
</div>
@endsection
