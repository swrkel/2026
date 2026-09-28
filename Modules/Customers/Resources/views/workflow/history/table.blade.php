<table class="table table-bordered table-striped customer-workflow-table">
    <thead><tr><th>Date</th><th>Customer</th><th>Code</th><th>Type</th><th>Action</th><th>Old</th><th>New</th><th>User</th><th>Remarks</th></tr></thead>
    <tbody>
        @forelse($history as $row)
            <tr>
                <td>{{ $row->created_at }}</td>
                <td>{{ $row->customer_name }}</td>
                <td>{{ $row->customer_code }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $row->workflow_type)) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $row->action)) }}</td>
                <td>{{ $row->old_value }}</td>
                <td>{{ $row->new_value }}</td>
                <td>{{ trim($row->user_name) }}</td>
                <td>{{ $row->remarks }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted">No workflow history found.</td></tr>
        @endforelse
    </tbody>
</table>
