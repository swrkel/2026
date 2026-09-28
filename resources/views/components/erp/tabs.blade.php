@props(['items' => [], 'active' => null, 'class' => ''])
<div {{ $attributes->merge(['class' => 'erp-tabs '.$class]) }}>
    @foreach($items as $key => $item)
        @php
            $isActive = ($active !== null && $active == $key) || (!empty($item['active']));
            $href = $item['href'] ?? ('#'.($item['target'] ?? $key));
        @endphp
        <a href="{{ $href }}" class="erp-tab {{ $isActive ? 'active' : '' }}" @if(!empty($item['target'])) data-toggle="tab" @endif>
            @if(!empty($item['icon'])) <i class="{{ $item['icon'] }}"></i> @endif
            <span>{{ $item['label'] ?? $key }}</span>
        </a>
    @endforeach
    {{ $slot }}
</div>
