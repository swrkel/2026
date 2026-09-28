@extends('bankingtesterui::layout')
@section('banking_tester_content')
<div class="bkg-card">
    <h3>Record Banking UI Issue</h3>
    <form method="POST" action="{{ route('banking.tester-ui.issues.store') }}" class="bkg-form">@csrf
        <label>Module Key<input name="module_key" required placeholder="banking_core_deposits"></label>
        <label>Page Title<input name="page_title" placeholder="Savings Account List"></label>
        <label>Route Name<input name="route_name" placeholder="banking.deposits.index"></label>
        <label>URL<input name="url" placeholder="/banking/... "></label>
        <label>Severity<select name="severity"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></label>
        <label>Summary<textarea name="summary" required></textarea></label>
        <label>Steps to Reproduce<textarea name="steps_to_reproduce"></textarea></label>
        <label>Expected Result<textarea name="expected_result"></textarea></label>
        <label>Actual Result<textarea name="actual_result"></textarea></label>
        <label>Screenshot Reference<input name="screenshot_reference" placeholder="Screenshot filename or note"></label>
        <button class="bkg-btn">Save Issue</button>
    </form>
</div>
@endsection
