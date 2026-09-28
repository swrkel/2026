@extends('tailoring::layouts.app')
@section('page_title', 'Measurement Profiles')
@section('tailoring_content')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Measurement Profiles</h3></div><div class="box-body">
<form method="POST" action="{{ route('tailoring.measurement-profiles.store') }}">@csrf
<div class="row"><div class="col-md-3"><input class="form-control" name="profile_name" placeholder="Office Shirts / Wedding Suit" required></div><div class="col-md-3"><input class="form-control" name="garment_type" placeholder="Garment Type"></div><div class="col-md-6"><input class="form-control" name="fitting_notes" placeholder="Fitting / style notes"></div></div><br><button class="btn btn-primary">Add Profile</button>
</form><hr>
<table class="table table-bordered"><thead><tr><th>Profile</th><th>Garment</th><th>Version</th><th>Status</th></tr></thead><tbody>@forelse($profiles as $p)<tr><td>{{ $p->profile_name }}</td><td>{{ $p->garment_type }}</td><td>{{ $p->version_no }}</td><td>{{ $p->is_active ? 'Active' : 'Inactive' }}</td></tr>@empty<tr><td colspan="4" class="text-center">No profiles</td></tr>@endforelse</tbody></table>
</div></div>
@endsection
