@props(['title'=>null,'subtitle'=>null,'class'=>''])
<section {{ $attributes->merge(['class'=>'pn-card '.$class]) }}>
    @if($title || isset($actions))
    <header class="pn-card-header"><div>@if($title)<h3>{{ $title }}</h3>@endif @if($subtitle)<p>{{ $subtitle }}</p>@endif</div>@if(isset($actions))<div class="pn-card-actions">{{ $actions }}</div>@endif</header>
    @endif
    <div class="pn-card-body">{{ $slot }}</div>
</section>
