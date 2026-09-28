<table class="table table-bordered table-sm banking-smoke-table">
    <thead>
        <tr>
            <th>Check</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
        @foreach($checks as $check)
            <tr>
                <td>{{ $check['key'] ?? $check['item'] ?? '-' }}</td>
                <td><span class="badge badge-{{ ($check['status'] ?? '') === 'pass' ? 'success' : (($check['status'] ?? '') === 'fail' ? 'danger' : 'warning') }}">{{ strtoupper($check['status'] ?? 'review') }}</span></td>
                <td>{{ $check['message'] ?? 'Manual verification required' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
