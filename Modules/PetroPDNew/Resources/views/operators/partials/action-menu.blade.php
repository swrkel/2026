@php
    $menuId = $menuId ?? ('workspace-' . uniqid());
    $items = $items ?? [];
@endphp
<button type="button" class="pdn-btn small primary" data-pdn-operator-actions="{{ $menuId }}">
    Actions <i class="fa fa-caret-down"></i>
</button>
<template data-pdn-operator-actions-template="{{ $menuId }}">
    @foreach($items as $item)
        @if(($item['type'] ?? '') === 'heading')
            <div class="pdn-action-section-title"><i class="{{ $item['icon'] ?? 'fa fa-cog' }}"></i> {{ $item['label'] }}</div>
        @elseif(($item['type'] ?? '') === 'divider')
            <div class="pdn-action-divider"></div>
        @elseif(($item['disabled'] ?? false))
            <span class="disabled" title="{{ $item['title'] ?? '' }}"><i class="{{ $item['icon'] ?? 'fa fa-ban' }}"></i> {{ $item['label'] }}</span>
        @else
            <a href="{{ $item['url'] }}" class="{{ $item['class'] ?? '' }}" @if($item['modal'] ?? false) data-pdn-operator-modal @endif>
                <i class="{{ $item['icon'] ?? 'fa fa-eye' }}"></i> {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</template>
