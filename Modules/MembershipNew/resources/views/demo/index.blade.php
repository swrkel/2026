@extends('membershipnew::layouts.app')
@php
    $title = 'Membership-New Demo / Testing Starter';
@endphp
@section('membership-content')
<div class="mn-panel">
    <h3>Current Business Testing Summary</h3>
    <table class="mn-table">
        <tbody>
            @foreach($summary as $key => $value)
                <tr>
                    <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mn-grid" style="margin-top:14px">
    <a class="mn-card" href="{{ route('membership-new.central-members.index') }}"><span>Step 1</span><strong>Central Members</strong></a>
    <a class="mn-card" href="{{ route('membership-new.business-members.index') }}"><span>Step 2</span><strong>Business Link</strong></a>
    <a class="mn-card" href="{{ route('membership-new.point-rules.index') }}"><span>Step 3</span><strong>Point Rules</strong></a>
    <a class="mn-card" href="{{ route('membership-new.points.index') }}"><span>Step 4</span><strong>Earn/Redeem</strong></a>
    <a class="mn-card" href="{{ route('membership-new.shares.index') }}"><span>Step 5</span><strong>Shares</strong></a>
    <a class="mn-card" href="{{ route('membership-new.dividends.index') }}"><span>Step 6</span><strong>Dividends</strong></a>
    <a class="mn-card" href="{{ route('membership-new.cards.scan-page') }}"><span>Step 7</span><strong>Card Scan</strong></a>
    <a class="mn-card" href="{{ route('membership-new.business-statement.index') }}"><span>Step 8</span><strong>Statement</strong></a>
</div>
@endsection
