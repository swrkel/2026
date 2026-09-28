{{-- Distribution module-local DataTable export button partial.
     Kept inside module to avoid direct dependency on layouts.partials.datatable_export_button. --}}
@if(isset($dataTable))
    {!! $dataTable->buttons() !!}
@else
    <div class="dt-buttons btn-group"></div>
@endif
