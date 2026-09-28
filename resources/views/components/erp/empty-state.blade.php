@props(['title' => 'No records found', 'message' => 'There is no data to display.'])
<div {{ $attributes->merge(['class' => 'erp-exf-empty']) }}>
    <div class="erp-exf-empty-title">{{ $title }}</div>
    <div>{{ $message }}</div>
</div>
