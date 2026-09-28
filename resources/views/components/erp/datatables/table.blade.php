@props([
    'id' => 'erp_datatable',
    'class' => '',
    'compact' => true,
    'footer' => false,
])
<div class="erp-table-wrap">
    <table id="{{ $id }}" class="table table-bordered table-striped erp-datatable {{ $compact ? 'erp-datatable--compact' : '' }} {{ $class }}" width="100%">
        {{ $slot }}
        @if($footer)
            <tfoot>{{ $footer }}</tfoot>
        @endif
    </table>
</div>
