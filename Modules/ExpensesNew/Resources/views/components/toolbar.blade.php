@php
    $resolvedCreateRoute = $createRoute ?? $create ?? '#';
    $resolvedCreateLabel = $createLabel ?? '+ Add';
@endphp
<div class="expnew-toolbar">
    <div><input type="text" class="form-control expnew-search" placeholder="Search"></div>
    <div class="expnew-toolbar-actions">
        <a href="{{ $resolvedCreateRoute }}" class="btn btn-primary">{{ $resolvedCreateLabel }}</a>
        <button type="button" class="btn btn-success expnew-export">Excel</button>
        <button type="button" class="btn btn-danger expnew-print">Print</button>
        <button type="button" class="btn btn-dark expnew-columns">Columns</button>
    </div>
</div>
