@extends('membershipnew::layouts.app')
@php
    $title = 'Create Dividend Batch';
@endphp
@section('membership-content')
<div class="mn-panel"><form method="POST" action="{{ route('membership-new.dividends.store') }}">@csrf<div class="mn-form-grid"><label>Dividend Date <input type="date" name="dividend_date" value="{{ now()->format('Y-m-d') }}"></label><label>Total Dividend Amount <input type="number" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal" name="total_dividend_amount" required></label><label class="mn-wide">Note <textarea name="note"></textarea></label></div><button class="mn-btn mn-btn-purple">Create and Calculate</button></form></div>
@endsection
