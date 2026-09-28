@php
    $title = $title ?? 'No records found';
    $message = $message ?? 'There are no records to display yet.';
    $icon = $icon ?? 'fa-info-circle';
    $url = $url ?? null;
    $label = $label ?? null;
@endphp
<div class="cheq-empty-state">
    <div class="cheq-empty-icon"><i class="fa {{ $icon }}"></i></div>
    <div class="cheq-empty-title">{{ $title }}</div>
    <div class="cheq-empty-text">{{ $message }}</div>
    @if($url && $label)
        <a class="cheq-btn" href="{{ $url }}">{{ $label }}</a>
    @endif
</div>
