@extends('layouts.app')
@section('title', 'Preventive Care Report')
@section('content')
<section class="content-header"><h1>My Health <small>Preventive Care / Follow-up Report</small></h1></section>
<section class="content">
    @include('myhealthmembers::ai_clinical._filters')
    <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title">Preventive Care Reminders</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Due Date</th><th>Member Code</th><th>Member</th><th>Type</th><th>Priority</th><th>Reminder</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($data as $row)
                    <tr>
                        <td>{{ $row->due_date ?? '' }}</td><td>{{ $row->member_code ?? '' }}</td><td>{{ $row->member_name ?? '' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $row->reminder_type ?? '')) }}</td><td>{{ ucfirst($row->priority ?? '') }}</td><td>{{ $row->title ?? '' }}</td><td>{{ ucfirst($row->status ?? '') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No preventive care reminders found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
