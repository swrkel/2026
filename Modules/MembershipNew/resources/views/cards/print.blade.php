@extends('membershipnew::layouts.app')
@php
    $title = 'Membership Identity Card';
@endphp
@section('membership-content')
<div class="mn-print-wrap">
    <div class="mn-id-card">
        <div class="mn-id-top">{{ __('membershipnew::messages.module_name') }}</div>
        <div class="mn-id-name">{{ trim($member->first_name . ' ' . $member->last_name) }}</div>
        <div class="mn-id-row">Code: {{ $member->member_code }}</div>
        <div class="mn-id-row">Mobile: {{ $member->mobile }}</div>
        <div class="mn-id-row">Card: {{ $card->card_no ?? 'Not issued' }}</div>
        <div class="mn-qr">{{ $card->card_no ?? 'Issue card first' }}</div>
    </div>
    <button onclick="window.print()" class="mn-btn mn-btn-info">Print</button>
</div>
@endsection
