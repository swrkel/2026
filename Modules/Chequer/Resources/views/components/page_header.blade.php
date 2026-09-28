@php
    $eyebrow = $eyebrow ?? 'Chequer Module';
    $title = $title ?? 'Chequer';
    $subtitle = $subtitle ?? '';
    $actions = $actions ?? [];
@endphp
<div class="cheq-top cheq-page-header-v2">
    <div>
        <div class="cheq-eyebrow">{{ $eyebrow }}</div>
        <div class="cheq-title">{{ $title }}</div>
        @if(!empty($subtitle))
            <div class="cheq-sub">{!! $subtitle !!}</div>
        @endif
    </div>
    @if(!empty($actions))
        <div class="cheq-header-actions">
            @foreach($actions as $action)
                <a class="cheq-btn {{ $action['class'] ?? '' }}" href="{{ $action['url'] ?? '#' }}">
                    @if(!empty($action['icon']))<i class="fa {{ $action['icon'] }}"></i>@endif
                    {{ $action['label'] ?? 'Open' }}
                </a>
            @endforeach
        </div>
    @endif
</div>
