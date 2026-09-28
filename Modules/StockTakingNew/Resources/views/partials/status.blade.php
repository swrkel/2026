@php
    $status = (string) ($status ?? 'draft');
    $map = [
        'draft' => 'grey', 'inactive' => 'grey', 'pending' => 'grey',
        'prepared' => 'blue', 'assigned' => 'blue', 'downloaded' => 'blue',
        'counting' => 'cyan', 'counted' => 'cyan',
        'recount' => 'orange', 'recount_required' => 'orange', 'recounted' => 'orange',
        'submitted' => 'purple', 'under_review' => 'purple', 'link_ready' => 'purple',
        'approved' => 'green', 'auto_approved' => 'green', 'posted' => 'green',
        'active' => 'green', 'sent' => 'green',
        'rejected' => 'red', 'cancelled' => 'red', 'failed' => 'red',
    ];
@endphp
<span class="stk-status {{ $map[$status] ?? 'grey' }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
