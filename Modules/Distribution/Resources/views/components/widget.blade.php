{{--
    Distribution-owned widget component.
    DIST-328: renders named tool slot (Add buttons) and removes default collapse/minus
    button from Distribution Settings widgets. The previous component ignored @slot('tool'),
    so Settings tabs showed only a minus icon and no Add button.
--}}
@php
    $boxClass = $class ?? 'box-primary';
    $boxTitle = $title ?? null;
@endphp
<div class="box {{ $boxClass }} distribution-widget-box">
    @if(!empty($boxTitle) || isset($tool))
        <div class="box-header with-border clearfix">
            @if(!empty($boxTitle))
                <h3 class="box-title">{!! $boxTitle !!}</h3>
            @endif
            @if(isset($tool))
                <div class="box-tools pull-right">
                    {!! $tool !!}
                </div>
            @endif
        </div>
    @endif
    <div class="box-body">
        {{ $slot }}
    </div>
</div>
