@extends('bankingmicrofinance::layouts.app')
@section('page-title','Group Details')
@section('module-content')
<div class="box"><div class="box-body"><h4>{{ $group->group_no }} - {{ $group->name }}</h4><p>Center: {{ $group->center_name }} | Meeting: {{ $group->meeting_day }} {{ $group->meeting_time }} | Officer: {{ $group->field_officer }}</p></div></div>
<div class="box"><div class="box-header"><h3 class="box-title">Members</h3></div><div class="box-body"><table class="table table-bordered"><tr><th>No</th><th>Name</th><th>NIC</th><th>Mobile</th><th>Status</th></tr>@foreach($members as $member)<tr><td>{{ $member->member_no }}</td><td>{{ $member->name }}</td><td>{{ $member->nic_no }}</td><td>{{ $member->mobile }}</td><td>{{ $member->status }}</td></tr>@endforeach</table>{{ $members->links() }}</div></div>
@endsection
