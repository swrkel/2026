<div class="ran-page-header">
    <div>
        <div class="ran-eyebrow">RAN JEWELLERY</div>
        <h1>{{ $title }}</h1>
        @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
    </div>
    <div class="ran-page-actions">{{ $slot }}</div>
</div>
