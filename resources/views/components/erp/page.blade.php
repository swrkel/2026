@props([
    'title' => null,
    'subtitle' => null,
    'layout' => 'standard',
    'accent' => null,
    'bodyClass' => '',
])
<div class="erp-page erp-page--{{ $layout }} {{ $bodyClass }}" @if($accent) style="--erp-accent: {{ $accent }};" @endif>
    @if($title || $subtitle)
        <x-erp.page-header :title="$title" :subtitle="$subtitle" />
    @endif
    {{ $slot }}
</div>
