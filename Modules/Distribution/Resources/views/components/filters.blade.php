{{-- Distribution-owned filters component.
     DIST-328: remove default collapse/minus button because it was hiding settings
     contents/table headers and confusing the tab pages. --}}
@php
    $filterTitle = $title ?? __('report.filters');
@endphp
<div class="box box-primary distribution-filter-box">
    <div class="box-header with-border">
        <h3 class="box-title">{!! $filterTitle !!}</h3>
    </div>
    <div class="box-body">
        {{ $slot }}
    </div>
</div>
