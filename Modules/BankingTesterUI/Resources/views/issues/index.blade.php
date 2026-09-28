@extends('bankingtesterui::layout')
@section('banking_tester_content')
@include('bankingtesterui::partials.toolbar')
<div class="bkg-card">
    <div class="bkg-row between"><h3>Banking Tester Issue Log</h3><a class="bkg-btn" href="{{ route('banking.tester-ui.issues.create') }}">Add Issue</a></div>
    <table class="bkg-table">
        <thead><tr><th>ID</th><th>Module</th><th>Severity</th><th>Status</th><th>Summary</th><th>Route</th><th>Action</th></tr></thead>
        <tbody>
        @forelse($issues as $issue)
            <tr>
                <td>{{ $issue->id }}</td><td>{{ $issue->module_key }}</td><td>{{ ucfirst($issue->severity) }}</td><td>{{ ucfirst($issue->status) }}</td>
                <td>{{ $issue->summary }}</td><td>{{ $issue->route_name }}</td>
                <td><form method="POST" action="{{ route('banking.tester-ui.issues.status', $issue) }}">@csrf<select name="status"><option value="checking">Checking</option><option value="fixed">Fixed</option><option value="retest">Retest</option><option value="closed">Closed</option></select><button class="bkg-small">Save</button></form></td>
            </tr>
        @empty
            <tr><td colspan="7">No banking UI issues recorded yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $issues->links() }}
</div>
@endsection
