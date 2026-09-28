@extends('myhealthmembers::portal.layout')
@section('title', 'My Profile')
@section('content')
<div class="mh-card"><div class="mh-card-header">My Profile</div><div class="mh-card-body">
@if(empty($member))<div class="alert alert-warning">Profile not found.</div>@else
<div class="row"><div class="col-sm-6"><p><b>Member Code:</b> {{ $member->myhealth_code ?? $member->member_code ?? '' }}</p><p><b>Name:</b> {{ $member->name }}</p><p><b>Mobile:</b> {{ $member->mobile }}</p><p><b>Email:</b> {{ $member->email }}</p></div><div class="col-sm-6"><p><b>NIC:</b> {{ $member->nic_no }}</p><p><b>Passport:</b> {{ $member->passport_no }}</p><p><b>Date of Birth:</b> {{ optional($member->date_of_birth)->format('Y-m-d') }}</p><p><b>Gender:</b> {{ $member->gender }}</p></div></div>
<hr><p><b>Address:</b><br>{{ $member->address }}</p><p><b>Emergency Contact:</b> {{ $member->emergency_contact_name }} {{ $member->emergency_contact_mobile ? ' - '.$member->emergency_contact_mobile : '' }}</p>
@endif
</div></div>
@endsection
