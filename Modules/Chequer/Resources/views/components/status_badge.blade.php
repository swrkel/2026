@php
    $status = strtolower((string)($status ?? 'active'));
@endphp
<span class="cheq-badge cheq-status-{{ $status }}">{{ ucwords(str_replace('_',' ', $status)) }}</span>
