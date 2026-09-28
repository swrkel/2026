@extends('layouts.app')
@section('title', 'Medication Safety Report')
@section('content')
<section class="content-header"><h1>My Health <small>Medication Safety Report</small></h1></section>
<section class="content">
    @include('myhealthmembers::ai_clinical._filters')
    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Medication / Allergy / Duplicate Drug Alerts</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Date</th><th>Member Code</th><th>Member</th><th>Severity</th><th>Alert</th><th>Message</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($data as $row)
                    <tr>
                        <td>{{ $row->created_at ?? '' }}</td><td>{{ $row->member_code ?? '' }}</td><td>{{ $row->member_name ?? '' }}</td>
                        <td>{{ ucfirst($row->severity ?? '') }}</td><td>{{ $row->title ?? '' }}</td><td>{{ $row->message ?? '' }}</td><td>{{ ucfirst($row->status ?? '') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No medication safety alerts found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
