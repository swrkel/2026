@props(['action'=>null,'method'=>'GET'])
<form method="{{ $method }}" action="{{ $action }}" class="atn-filter-toolbar">
    <div class="atn-filter-left">{{ $filters ?? '' }}</div>
    <div class="atn-filter-actions">{{ $actions ?? '' }}</div>
</form>
