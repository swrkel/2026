@php
    $toolTableId = $tableId ?? 'pdn-workspace-table';
    $toolFileName = $fileName ?? 'petro-pd-new-export';
    $toolTitle = $title ?? 'Petro PD-New';
    $toolColumns = $columns ?? [];
@endphp
<div class="pdn-actions pdn-table-tools no-print">
    <button class="pdn-btn success" type="button" data-pdn-table-export="csv" data-table-id="{{ $toolTableId }}" data-file-name="{{ $toolFileName }}">
        <i class="fa fa-file-text-o"></i> CSV
    </button>
    <button class="pdn-btn warning" type="button" data-pdn-table-export="excel" data-table-id="{{ $toolTableId }}" data-file-name="{{ $toolFileName }}">
        <i class="fa fa-file-excel-o"></i> Excel
    </button>
    <button class="pdn-btn danger" type="button" data-pdn-table-pdf="{{ $toolTableId }}" data-print-title="{{ $toolTitle }}">
        <i class="fa fa-file-pdf-o"></i> PDF
    </button>
    <button class="pdn-btn light" type="button" data-pdn-table-print="{{ $toolTableId }}" data-print-title="{{ $toolTitle }}">
        <i class="fa fa-print"></i> Print
    </button>
    @if(count($toolColumns))
        <details class="pdn-column-menu">
            <summary class="pdn-btn purple"><i class="fa fa-columns"></i> Column Visibility</summary>
            <div class="pdn-popover">
                @foreach($toolColumns as $columnIndex => $columnLabel)
                    <label class="pdn-check">
                        <input type="checkbox" checked data-pdn-column-toggle="{{ $toolTableId }}" data-column-index="{{ $columnIndex }}">
                        {{ $columnLabel }}
                    </label>
                @endforeach
            </div>
        </details>
    @endif
</div>
