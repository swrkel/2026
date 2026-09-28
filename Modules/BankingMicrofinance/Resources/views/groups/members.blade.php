@extends('bankingmicrofinance::layouts.app')
@section('page-title','Microfinance Members')
@section('module-content')
<div class="box"><div class="box-header"><a href="{{ route('banking.microfinance.members.create') }}" class="btn btn-primary">Add Member</a></div><div class="box-body table-responsive"><table class="table table-bordered"><tr><th>Member No</th><th>Name</th><th>Group</th><th>NIC</th><th>Mobile</th><th>Savings</th><th>Status</th><th>Action</th></tr>@foreach($members as $member)<tr><td>{{ $member->member_no }}</td><td>{{ $member->name }}</td><td>{{ optional($member->group)->name }}</td><td>{{ $member->nic_no }}</td><td>{{ $member->mobile }}</td><td>{{ number_format($member->compulsory_saving_balance,4) }}</td><td>{{ $member->status }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('banking.microfinance.members.edit',$member) }}">Edit</a></td></tr>@endforeach</table>{{ $members->links() }}</div></div>
@endsection
