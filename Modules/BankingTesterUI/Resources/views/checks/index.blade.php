@extends('bankingtesterui::layout')
@section('banking_tester_content')
@include('bankingtesterui::partials.toolbar')
<div class="bkg-card">
    <h3>Banking Tester Checklist Results</h3>
    <table class="bkg-table">
        <thead><tr><th>Module</th><th>Check</th><th>Result</th><th>Notes</th><th>Save</th></tr></thead>
        <tbody>
        @foreach($checks as $check)
            <tr><form method="POST" action="{{ route('banking.tester-ui.checks.store') }}">@csrf
                <td>{{ $check['module_key'] }}<input type="hidden" name="module_key" value="{{ $check['module_key'] }}"><input type="hidden" name="check_key" value="{{ $check['check_key'] }}"><input type="hidden" name="check_title" value="{{ $check['title'] }}"></td>
                <td>{{ $check['title'] }}</td>
                <td><select name="result"><option value="pending">Pending</option><option value="pass">Pass</option><option value="fail">Fail</option><option value="blocked">Blocked</option></select></td>
                <td><input name="notes" placeholder="Tester notes"></td>
                <td><button class="bkg-small">Save</button></td>
            </form></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
