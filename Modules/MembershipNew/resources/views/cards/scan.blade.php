@extends('membershipnew::layouts.app')
@php
    $title = 'Membership Card Scan / Lookup';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.cards.lookup') }}">
        @csrf
        <div class="mn-form-grid">
            <label>Scan / Enter Card No
                <input name="card_no" autofocus required placeholder="MNC-...">
            </label>
        </div>
        <button class="mn-btn mn-btn-info">Lookup Member</button>
    </form>
</div>

@if(isset($card))
<div class="mn-grid" style="margin-top:14px">
    <div class="mn-card"><span>Member</span><strong>{{ optional($card->member)->member_code }} - {{ optional($card->member)->first_name }}</strong></div>
    <div class="mn-card"><span>Card No</span><strong>{{ $card->card_no }}</strong></div>
    <div class="mn-card"><span>Point Balance</span><strong>{{ number_format($balance, 4) }}</strong></div>
    <div class="mn-card"><span>Expiry</span><strong>{{ optional($card->expires_on)->format('Y-m-d') ?? 'No Expiry' }}</strong></div>
</div>
@endif
@endsection
