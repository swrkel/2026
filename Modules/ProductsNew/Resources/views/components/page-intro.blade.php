@props(['title', 'subtitle' => null, 'eyebrow' => null])
<div class="pn-page-intro">
    <div>
        @if($eyebrow)<div class="pn-eyebrow">{{ $eyebrow }}</div>@endif
        <h2>{{ $title }}</h2>
        @if($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    @if(isset($actions))<div class="pn-page-actions">{{ $actions }}</div>@endif
</div>
