@php
    $value = strtolower((string)($status ?? 'neutral'));
    $map = ['sent'=>'success','delivered'=>'success','active'=>'success','completed'=>'success','pending'=>'warning','queued'=>'warning','processing'=>'info','failed'=>'danger','cancelled'=>'danger','inactive'=>'neutral'];
    $class = $map[$value] ?? 'neutral';
@endphp
<span class="erp-ui-badge erp-ui-badge-{{ $class }}">{{ ucfirst($status ?? 'N/A') }}</span>
