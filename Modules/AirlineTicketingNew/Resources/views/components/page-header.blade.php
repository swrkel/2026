@props(['title','subtitle'=>null])
<div class="atn-page-header">
    <div>
        <h2>{{ $title }}</h2>
        @if($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    <div class="atn-page-actions">{{ $actions ?? '' }}</div>
</div>
