@extends('membershipnew::layouts.app')
@php
    $title = 'Business Access Rules';
@endphp
@section('membership-content')
<div class="mn-panel">
    <form method="POST" action="{{ route('membership-new.business-access.update') }}">
        @csrf
        <div class="mn-form-grid">
            <label><input type="checkbox" name="can_view_central_profile" value="1" {{ $rule->can_view_central_profile ? 'checked' : '' }}> Can view central profile</label>
            <label><input type="checkbox" name="can_view_other_business_history" value="1" {{ $rule->can_view_other_business_history ? 'checked' : '' }}> Can view other business history</label>
            <label><input type="checkbox" name="can_redeem_cross_business_points" value="1" {{ $rule->can_redeem_cross_business_points ? 'checked' : '' }}> Can redeem cross-business points</label>
            <label><input type="checkbox" name="can_issue_card" value="1" {{ $rule->can_issue_card ? 'checked' : '' }}> Can issue cards</label>
            <label><input type="checkbox" name="is_active" value="1" {{ $rule->is_active ? 'checked' : '' }}> Active</label>
            <label class="mn-wide">Note <textarea name="note">{{ $rule->note }}</textarea></label>
        </div>
        <button class="mn-btn mn-btn-success">Save Access Rules</button>
    </form>
</div>
@endsection
