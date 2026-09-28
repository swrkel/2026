@extends('layouts.app')
@section('title', 'My Health Business Access')
@section('content')
<section class="content-header"><h1>My Health Business Access</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Access Requests</h3>
            <div class="box-tools"><a href="{{ route('myhealth.access.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Request Access</a></div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Date</th><th>Member Code</th><th>Purpose</th><th>Sections</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($requests as $row)
                    <tr>
                        <td>{{ $row->created_at }}</td>
                        <td>{{ $row->member_code }}</td>
                        <td>{{ $row->purpose }}</td>
                        <td>{{ implode(', ', json_decode($row->access_sections ?? '[]', true) ?: []) }}</td>
                        <td><span class="label label-info">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</span></td>
                        <td>
                            @if($row->status === 'approved')
                                <form method="POST" action="{{ route('myhealth.access.revoke', $row->id) }}">@csrf<button class="btn btn-danger btn-xs">Revoke</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No access requests found.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $requests->links() }}
        </div>
    </div>
</section>
@endsection
